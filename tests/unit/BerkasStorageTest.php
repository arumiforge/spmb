<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Berkas\Services\DocumentStorage;
use Modules\Berkas\Services\UploadRejected;
use Tests\Support\Berkas\BerkasTestHelpers;

/**
 * File checks, resizing and EXIF stripping (FR-DOC-02, D-036).
 *
 * @internal
 */
final class BerkasStorageTest extends CIUnitTestCase
{
    use BerkasTestHelpers;
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;
    private string $root;
    private DocumentStorage $storage;

    /**
     * @var array<string, mixed>
     */
    private array $type = ['id' => 1, 'code' => 'kk', 'max_size_kb' => 2048, 'extensions' => 'jpg,jpeg,png,pdf'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->root    = sys_get_temp_dir() . '/berkas-storage-' . bin2hex(random_bytes(4));
        $this->storage = new DocumentStorage($this->root);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*/*') ?: [] as $file) {
            unlink($file);
        }

        foreach (glob($this->root . '/*') ?: [] as $dir) {
            rmdir($dir);
        }

        @rmdir($this->root);
        $this->removeStoredFiles();
        parent::tearDown();
    }

    public function testCameraPhotoIsTurnedUprightResizedAndStrippedOfExif(): void
    {
        $stored = $this->storage->store($this->tmpCopy('camera-rotated.jpg'), 'IMG_2026.JPG', $this->type);

        $this->assertMatchesRegularExpression('#\A\d{4}/[a-f0-9]{32}\.jpg\z#', $stored['file_path']);
        $this->assertSame('image/jpeg', $stored['mime']);
        $this->assertSame('IMG_2026.JPG', $stored['original_name']);

        $path = $this->storage->path($stored['file_path']);
        $this->assertNotNull($path);

        [$width, $height] = getimagesize($path);
        // 2400×1200 landscape with orientation 6 → upright portrait, longest side 1600.
        $this->assertSame(1600, $height);
        $this->assertSame(800, $width);

        // The red left half of the sensor image ends up on top (rotated 90° clockwise).
        $image = imagecreatefromjpeg($path);
        $top   = imagecolorsforindex($image, imagecolorat($image, 400, 100));
        $this->assertGreaterThan(150, $top['red']);
        $this->assertLessThan(100, $top['blue']);

        $bytes = (string) file_get_contents($path);
        $this->assertStringNotContainsString('Exif', $bytes);
        $this->assertStringNotContainsString('SPMBCAM', $bytes);
    }

    public function testImageSettingsAreUsed(): void
    {
        setting('Berkas.imageMaxEdge', 1000);

        $stored           = $this->storage->store($this->tmpCopy('camera-rotated.jpg'), 'foto.jpg', $this->type);
        [$width, $height] = getimagesize((string) $this->storage->path($stored['file_path']));

        $this->assertSame([500, 1000], [$width, $height]);
    }

    public function testTransparentPngBecomesJpegOnWhite(): void
    {
        $stored = $this->storage->store($this->tmpCopy('transparent.png'), 'scan.png', $this->type);
        $path   = (string) $this->storage->path($stored['file_path']);

        $this->assertSame('image/jpeg', (new finfo(FILEINFO_MIME_TYPE))->file($path));
        $image = imagecreatefromjpeg($path);
        $rgb   = imagecolorsforindex($image, imagecolorat($image, 5, 5));
        $this->assertGreaterThan(240, $rgb['red']);
        $this->assertGreaterThan(240, $rgb['blue']);
        [$width] = getimagesize($path);
        $this->assertSame(300, $width, 'small images are not enlarged');
    }

    public function testPdfIsKeptAsUploaded(): void
    {
        $stored = $this->storage->store($this->tmpCopy('sample.pdf'), 'akta.pdf', $this->type);

        $this->assertStringEndsWith('.pdf', $stored['file_path']);
        $this->assertSame('application/pdf', $stored['mime']);
        $this->assertFileEquals($this->fixture('sample.pdf'), (string) $this->storage->path($stored['file_path']));
    }

    public function testTextRenamedToJpgIsRejected(): void
    {
        $this->expectException(UploadRejected::class);
        $this->expectExceptionMessage('Jenis file belum sesuai');
        $this->storage->store($this->tmpCopy('fake-photo.jpg'), 'foto.jpg', $this->type);
    }

    public function testExtensionNotAllowedForTheTypeIsRejected(): void
    {
        $this->expectException(UploadRejected::class);
        $this->expectExceptionMessage('Gunakan file JPG atau PNG');
        $this->storage->store($this->tmpCopy('sample.pdf'), 'foto.pdf', ['extensions' => 'jpg,jpeg,png', 'max_size_kb' => 1024]);
    }

    public function testRealPdfNamedAsJpgForImageOnlyTypeIsRejected(): void
    {
        $this->expectException(UploadRejected::class);
        $this->storage->store($this->tmpCopy('sample.pdf'), 'foto.jpg', ['extensions' => 'jpg,jpeg,png', 'max_size_kb' => 1024]);
    }

    public function testHeicGetsAHelpfulMessage(): void
    {
        $this->expectException(UploadRejected::class);
        $this->expectExceptionMessage('HEIC');
        $this->storage->store($this->tmpCopy('camera-rotated.jpg'), 'IMG_0001.HEIC', $this->type);
    }

    public function testPdfOverTheTypeLimitIsRejectedWithFriendlySize(): void
    {
        $big = $this->tmpCopy('sample.pdf');
        file_put_contents($big, str_repeat('%', 150 * 1024), FILE_APPEND);

        $this->expectException(UploadRejected::class);
        $this->expectExceptionMessage('Ukuran file terlalu besar. Maksimal 100 KB.');
        $this->storage->store($big, 'rapor.pdf', ['extensions' => 'pdf', 'max_size_kb' => 100]);
    }

    public function testLargePhotoIsAcceptedWhenItFitsAfterResizing(): void
    {
        // The camera fixture is ~45 KB; with a 40 KB limit only the resized copy fits.
        $stored = $this->storage->store($this->tmpCopy('camera-rotated.jpg'), 'foto.jpg', ['extensions' => 'jpg', 'max_size_kb' => 40]);

        $this->assertLessThanOrEqual(40 * 1024, $stored['size_bytes']);
    }

    public function testSizeLabels(): void
    {
        $this->assertSame('2 MB', DocumentStorage::sizeLabel(2048));
        $this->assertSame('1,5 MB', DocumentStorage::sizeLabel(1536));
        $this->assertSame('500 KB', DocumentStorage::sizeLabel(500));
    }

    public function testPathRejectsTraversal(): void
    {
        $this->assertNull($this->storage->path('../../.env'));
        $this->assertNull($this->storage->path('2026/../../x.jpg'));
        $this->assertNull($this->storage->path(null));
    }

    public function testCleanNameDropsPaths(): void
    {
        $this->assertSame('kk.jpg', DocumentStorage::cleanName('C:\\Users\\ibu\\kk.jpg'));
        $this->assertSame('passwd', DocumentStorage::cleanName('../../etc/passwd'));
    }
}
