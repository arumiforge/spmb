<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use Modules\Berkas\Services\DocumentStatus;

/**
 * Overall document status of an applicant (FR-DOC-04, D-036).
 *
 * @internal
 */
final class BerkasDocumentStatusTest extends CIUnitTestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private function types(): array
    {
        return [
            ['id' => 1, 'is_required' => 1, 'may_follow' => 0],
            ['id' => 2, 'is_required' => 1, 'may_follow' => 1],
            ['id' => 3, 'is_required' => 0, 'may_follow' => 0],
        ];
    }

    /**
     * @param array<int, string> $statuses type id => status
     *
     * @return array<int, array<string, mixed>>
     */
    private function docs(array $statuses): array
    {
        $docs = [];

        foreach ($statuses as $typeId => $status) {
            $docs[$typeId] = ['document_type_id' => $typeId, 'status' => $status];
        }

        return $docs;
    }

    public function testNothingUploadedIsIncomplete(): void
    {
        $this->assertSame('incomplete', DocumentStatus::compute($this->types(), []));
    }

    public function testMissingRequiredDocumentIsIncompleteEvenWithPendingOnes(): void
    {
        $this->assertSame('incomplete', DocumentStatus::compute($this->types(), $this->docs([1 => 'pending'])));
    }

    public function testInvalidRequiredDocumentIsIncomplete(): void
    {
        $this->assertSame('incomplete', DocumentStatus::compute($this->types(), $this->docs([1 => 'invalid', 2 => 'valid'])));
    }

    public function testSomethingWaitingForACheckIsPending(): void
    {
        $this->assertSame('pending', DocumentStatus::compute($this->types(), $this->docs([1 => 'valid', 2 => 'pending'])));
        $this->assertSame('pending', DocumentStatus::compute($this->types(), $this->docs([1 => 'valid', 2 => 'to_follow', 3 => 'pending'])));
    }

    public function testOnlyToFollowLeft(): void
    {
        $this->assertSame('to_follow', DocumentStatus::compute($this->types(), $this->docs([1 => 'valid', 2 => 'to_follow'])));
    }

    public function testToFollowOnATypeThatNoLongerAllowsItIsIncomplete(): void
    {
        $this->assertSame('incomplete', DocumentStatus::compute($this->types(), $this->docs([1 => 'to_follow', 2 => 'valid'])));
    }

    public function testAllRequiredValidIsCompleteRegardlessOfOptionalOnes(): void
    {
        $this->assertSame('complete', DocumentStatus::compute($this->types(), $this->docs([1 => 'valid', 2 => 'valid'])));
        $this->assertSame('complete', DocumentStatus::compute($this->types(), $this->docs([1 => 'valid', 2 => 'valid', 3 => 'invalid'])));
    }

    public function testNoRequiredTypesIsComplete(): void
    {
        $this->assertSame('complete', DocumentStatus::compute([['id' => 3, 'is_required' => 0, 'may_follow' => 0]], []));
    }
}
