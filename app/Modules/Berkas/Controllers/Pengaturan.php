<?php

declare(strict_types=1);

namespace Modules\Berkas\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\TemplateStorage;
use Modules\Berkas\Services\UploadInput;
use Modules\Berkas\Services\UploadRejected;

/**
 * Required documents (document types), their example files ("Unduh contoh")
 * and image settings (FR-DOC-01, FR-DOC-02). Permission pengaturan.pendaftaran.
 * Changes to a type, its example file included, are audited by the model.
 */
class Pengaturan extends BaseController
{
    use ServesTemplates;

    /**
     * Upload field of the example file.
     */
    public const TEMPLATE_FIELD = 'template';

    /**
     * Settings editable on this page (setting('Berkas.<key>')).
     *
     * @var list<string>
     */
    public const SETTINGS = ['imageMaxEdge', 'imageQuality', 'deadline'];

    private DocumentTypeModel $types;

    public function __construct()
    {
        $this->types = model(DocumentTypeModel::class);
    }

    public function index(): string
    {
        $values = [];

        foreach (self::SETTINGS as $key) {
            $values[$key] = (string) setting('Berkas.' . $key);
        }

        return view('Modules\Berkas\Views\settings', [
            'title'       => lang('Berkas.settingsTitle'),
            'subtitle'    => lang('Berkas.settingsIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Berkas Wajib']],
            'types'       => $this->types->ordered(),
            'values'      => $values,
        ]);
    }

    public function updateSettings(): RedirectResponse
    {
        $rules = [
            'imageMaxEdge' => ['label' => lang('Berkas.imageMaxEdge'), 'rules' => 'required|integer|greater_than_equal_to[600]|less_than_equal_to[4000]', 'errors' => ['required' => lang('Berkas.imageMaxEdgeError'), 'integer' => lang('Berkas.imageMaxEdgeError'), 'greater_than_equal_to' => lang('Berkas.imageMaxEdgeError'), 'less_than_equal_to' => lang('Berkas.imageMaxEdgeError')]],
            'imageQuality' => ['label' => lang('Berkas.imageQuality'), 'rules' => 'required|integer|greater_than_equal_to[10]|less_than_equal_to[95]', 'errors' => ['required' => lang('Berkas.imageQualityError'), 'integer' => lang('Berkas.imageQualityError'), 'greater_than_equal_to' => lang('Berkas.imageQualityError'), 'less_than_equal_to' => lang('Berkas.imageQualityError')]],
            'deadline'     => ['label' => lang('Berkas.deadline'), 'rules' => 'permit_empty|valid_date[Y-m-d]', 'errors' => ['valid_date' => lang('Berkas.deadlineError')]],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $old = [];
        $new = [];

        foreach (self::SETTINGS as $key) {
            $raw     = trim((string) $this->request->getPost($key));
            $value   = $key === 'deadline' ? $raw : (int) $raw;
            $current = setting('Berkas.' . $key);

            if ((string) $value !== (string) $current) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Berkas.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Berkas', $old, $new, 'Pengaturan berkas diubah');
        }

        return redirect()->route('berkas.pengaturan')->with('message', lang('Berkas.settingsSaved'));
    }

    public function new(): string
    {
        return $this->form(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->postedType(null);

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $template = $this->postedTemplate(null, $data);

        if (is_string($template)) {
            return redirect()->back()->withInput()->with('errors', [self::TEMPLATE_FIELD => $template]);
        }

        $data['sort_order'] = $this->types->nextSortOrder();

        if ($this->types->insert($data) === false) {
            (new TemplateStorage())->delete($template['stored']);

            return redirect()->back()->withInput()->with('error', lang('Berkas.typeNotSaved'));
        }

        return redirect()->route('berkas.pengaturan')->with('message', lang('Berkas.typeSaved'));
    }

    public function edit(int $id): RedirectResponse|string
    {
        $type = $this->types->find($id);

        if ($type === null) {
            return redirect()->route('berkas.pengaturan')->with('error', lang('Berkas.typeNotFound'));
        }

        return $this->form($type);
    }

    public function update(int $id): RedirectResponse
    {
        $current = $this->types->find($id);

        if ($current === null) {
            return redirect()->route('berkas.pengaturan')->with('error', lang('Berkas.typeNotFound'));
        }

        $data = $this->postedType($id);

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $template = $this->postedTemplate($current, $data);

        if (is_string($template)) {
            return redirect()->back()->withInput()->with('errors', [self::TEMPLATE_FIELD => $template]);
        }

        $storage = new TemplateStorage();

        if ($this->types->update($id, $data) === false) {
            $storage->delete($template['stored']);

            return redirect()->back()->withInput()->with('error', lang('Berkas.typeNotSaved'));
        }

        // The replaced or removed file goes only after the row points elsewhere.
        $storage->delete($template['old']);

        return redirect()->route('berkas.pengaturan')->with('message', lang('Berkas.typeSaved'));
    }

    /**
     * GET /admin/pengaturan/berkas/<id>/contoh — the stored example file, also
     * while it is switched off, so the school can check it.
     */
    public function template(int $id): ResponseInterface
    {
        $type = $this->types->find($id);

        if ($type === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->sendTemplate($type);
    }

    /**
     * Swaps a type with its neighbour (arah = naik | turun) and renumbers the list.
     */
    public function move(int $id): RedirectResponse
    {
        $list  = $this->types->ordered();
        $index = array_search($id, array_map('intval', array_column($list, 'id')), true);

        if ($index === false) {
            return redirect()->route('berkas.pengaturan')->with('error', lang('Berkas.typeNotFound'));
        }

        $target = $this->request->getPost('arah') === 'naik' ? $index - 1 : $index + 1;

        if (isset($list[$target])) {
            [$list[$index], $list[$target]] = [$list[$target], $list[$index]];

            foreach ($list as $position => $type) {
                $order = ($position + 1) * 10;

                if ((int) $type['sort_order'] !== $order) {
                    $this->types->update((int) $type['id'], ['sort_order' => $order]);
                }
            }
        }

        return redirect()->route('berkas.pengaturan');
    }

    /**
     * Soft-deletes a type nobody has uploaded yet; otherwise asks to hide it instead.
     */
    public function delete(int $id): RedirectResponse
    {
        if ($this->types->find($id) === null) {
            return redirect()->route('berkas.pengaturan')->with('error', lang('Berkas.typeNotFound'));
        }

        $used = model(ApplicantDocumentModel::class)->withDeleted()->where('document_type_id', $id)->countAllResults();

        if ($used > 0) {
            return redirect()->route('berkas.pengaturan')->with('error', lang('Berkas.deleteInUse'));
        }

        $type = $this->types->find($id);
        $this->types->delete($id);
        (new TemplateStorage())->delete($type['template_path'] ?? null);

        return redirect()->route('berkas.pengaturan')->with('message', lang('Berkas.deleted'));
    }

    /**
     * @param array<string, mixed>|null $type
     */
    private function form(?array $type): string
    {
        $title = $type === null ? lang('Berkas.addTypeTitle') : lang('Berkas.editTypeTitle');

        return view('Modules\Berkas\Views\type_form', [
            'title'       => $title,
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Berkas Wajib', 'url' => 'admin/pengaturan/berkas'], ['label' => $type === null ? 'Tambah' : 'Ubah']],
            'type'        => $type ?? [
                'code'          => '', 'name' => '', 'description' => '', 'is_required' => 1, 'may_follow' => 0,
                'max_size_kb'   => 2048, 'extensions' => 'jpg,jpeg,png,pdf', 'is_active' => 1,
                'template_path' => null, 'template_name' => null, 'template_enabled' => 0, 'template_label' => null,
            ],
            'isNew' => $type === null,
        ]);
    }

    /**
     * Validated type data from the form, or null (errors in $this->validator).
     *
     * @return array<string, mixed>|null
     */
    private function postedType(?int $id): ?array
    {
        $post             = $this->request->getPost();
        $post['code']     = strtolower(trim((string) ($post['code'] ?? '')));
        $formats          = array_values(array_intersect(['jpg', 'png', 'pdf'], (array) ($post['formats'] ?? [])));
        $post['formats_'] = implode(',', $formats);

        $rules = [
            'name'        => ['label' => lang('Berkas.fieldName'), 'rules' => 'required|max_length[100]'],
            'code'        => ['label' => lang('Berkas.fieldCode'), 'rules' => 'required|max_length[30]|regex_match[/\A[a-z0-9_-]+\z/]|is_unique[document_types.code,id,' . ($id ?? 0) . ']', 'errors' => ['regex_match' => lang('Berkas.fieldCodeError'), 'is_unique' => lang('Berkas.fieldCodeTaken')]],
            'description' => ['label' => lang('Berkas.fieldDescription'), 'rules' => 'permit_empty|max_length[255]'],
            'max_size_kb' => ['label' => lang('Berkas.fieldMaxSize'), 'rules' => 'required|integer|greater_than_equal_to[100]|less_than_equal_to[10240]', 'errors' => ['integer' => lang('Berkas.fieldMaxSizeError'), 'greater_than_equal_to' => lang('Berkas.fieldMaxSizeError'), 'less_than_equal_to' => lang('Berkas.fieldMaxSizeError')]],
            'formats_'    => ['label' => lang('Berkas.fieldFormats'), 'rules' => 'required', 'errors' => ['required' => lang('Berkas.fieldFormatsError')]],
            // Mirrors the column size (VARCHAR 60).
            'template_label' => ['label' => lang('Berkas.templateLabelField'), 'rules' => 'permit_empty|max_length[60]'],
        ];

        if (! $this->validateData($post, $rules)) {
            // The form shows the formats error under the checkboxes (field "formats").
            $errors = $this->validator->getErrors();

            if (isset($errors['formats_'])) {
                $this->validator->setError('formats', $errors['formats_']);
            }

            return null;
        }

        $extensions = [];

        foreach ($formats as $format) {
            array_push($extensions, ...($format === 'jpg' ? ['jpg', 'jpeg'] : [$format]));
        }

        return [
            'name'        => trim((string) $post['name']),
            'code'        => $post['code'],
            'description' => trim((string) ($post['description'] ?? '')) ?: null,
            'is_required' => (int) (($post['is_required'] ?? '0') === '1'),
            'may_follow'  => (int) (($post['may_follow'] ?? '0') === '1'),
            'is_active'   => (int) (($post['is_active'] ?? '0') === '1'),
            'max_size_kb' => (int) $post['max_size_kb'],
            'extensions'  => implode(',', $extensions),
            // Example file: switch and link text; the file itself comes from postedTemplate().
            'template_enabled' => (int) (($post['template_enabled'] ?? '0') === '1'),
            'template_label'   => trim((string) ($post['template_label'] ?? '')) ?: null,
        ];
    }

    /**
     * Example file from the form: a newly chosen file is checked and stored
     * now; "remove" clears it (and switches it off). Adds the template columns
     * to $data. Returns an error message for the file field, or the stored
     * file name (to remove if saving fails) and the old one (to remove after saving).
     *
     * @param array<string, mixed>|null $current document_types row being edited
     * @param array<string, mixed>      $data    validated type data, extended in place
     *
     * @return array{stored: string|null, old: string|null}|string
     */
    private function postedTemplate(?array $current, array &$data): array|string
    {
        $currentPath = $current['template_path'] ?? null;
        $hasFile     = $current !== null && DocumentTypeModel::hasTemplate($current);
        $stored      = null;
        $old         = null;

        if (UploadInput::sent($this->request, self::TEMPLATE_FIELD)) {
            try {
                $input = UploadInput::read($this->request, self::TEMPLATE_FIELD);
                $file  = (new TemplateStorage())->store($input['tmp'], $input['name']);
            } catch (UploadRejected $e) {
                return $e->getMessage();
            }

            $data += $file;
            $stored  = $file['template_path'];
            $old     = $currentPath;
            $hasFile = true;
        } elseif ($this->request->getPost('remove_template') === '1' && $currentPath !== null) {
            $data['template_path']    = null;
            $data['template_name']    = null;
            $data['template_enabled'] = 0;
            $old                      = $currentPath;
            $hasFile                  = false;
        }

        if ($data['template_enabled'] === 1 && ! $hasFile) {
            return lang('Berkas.templateErrNeedFile');
        }

        return ['stored' => $stored, 'old' => $old];
    }
}
