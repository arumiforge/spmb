<?php

declare(strict_types=1);

namespace Modules\Berkas\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;

/**
 * Required documents (document types) and image settings (FR-DOC-01, FR-DOC-02).
 * Permission pengaturan.pendaftaran.
 */
class Pengaturan extends BaseController
{
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

        $data['sort_order'] = $this->types->nextSortOrder();
        $this->types->insert($data);

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
        if ($this->types->find($id) === null) {
            return redirect()->route('berkas.pengaturan')->with('error', lang('Berkas.typeNotFound'));
        }

        $data = $this->postedType($id);

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->types->update($id, $data);

        return redirect()->route('berkas.pengaturan')->with('message', lang('Berkas.typeSaved'));
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

        $this->types->delete($id);

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
                'code' => '', 'name' => '', 'description' => '', 'is_required' => 1, 'may_follow' => 0,
                'max_size_kb' => 2048, 'extensions' => 'jpg,jpeg,png,pdf', 'is_active' => 1,
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
        ];
    }
}
