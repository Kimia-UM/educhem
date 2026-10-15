<?php

namespace Tests\Feature\Guru;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class GradeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_export_the_grade_summary_as_a_real_xlsx_workbook(): void
    {
        Role::findOrCreate('GURU', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('GURU');

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'class_name' => 'Kelas Uji',
            'class_code' => 'XLSX1',
        ]);

        $formulaLikeStudent = User::factory()->create([
            'name' => '=2+2',
            'email' => '=formula@example.test',
        ]);
        $regularStudent = User::factory()->create([
            'name' => 'Budi',
            'email' => 'budi@example.test',
        ]);

        $classroom->students()->attach($formulaLikeStudent->id, [
            'pre_test_score' => 88,
            'post_test_score' => null,
        ]);
        $classroom->students()->attach($regularStudent->id, [
            'pre_test_score' => 0,
            'post_test_score' => 100,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('guru.classes.export.grades', $classroom));

        $response->assertOk()
            ->assertDownload('Rekap_Nilai_Kelas_Uji.xlsx')
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );

        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $workbookPath = $response->baseResponse->getFile()->getPathname();

        try {
            $this->assertSame('PK', file_get_contents($workbookPath, false, null, 0, 2));

            $reader = new Reader;
            $reader->open($workbookPath);
            $rows = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                $this->assertSame('Rekap Nilai', $sheet->getName());

                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }

            $reader->close();

            $this->assertSame([
                ['No', 'Nama Siswa', 'Email', 'Nilai Awal (Pre-test)', 'Nilai Akhir (Post-test)'],
                [1, '=2+2', '=formula@example.test', 88, '-'],
                [2, 'Budi', 'budi@example.test', 0, 100],
            ], $rows);

            $archive = new ZipArchive;
            $this->assertTrue($archive->open($workbookPath));
            $sheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
            $archive->close();

            $this->assertIsString($sheetXml);
            $this->assertStringNotContainsString('<f>', $sheetXml);
        } finally {
            @unlink($workbookPath);
        }
    }

    public function test_another_teacher_cannot_export_the_class_grades(): void
    {
        Role::findOrCreate('GURU', 'web');
        $owner = User::factory()->create();
        $owner->assignRole('GURU');
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('GURU');

        $classroom = Classroom::create([
            'teacher_id' => $owner->id,
            'class_name' => 'Kelas Privat',
            'class_code' => 'XLSX2',
        ]);

        $this->actingAs($otherTeacher)
            ->get(route('guru.classes.export.grades', $classroom))
            ->assertForbidden();
    }
}
