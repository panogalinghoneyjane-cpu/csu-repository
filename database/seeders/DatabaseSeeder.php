<?php

namespace Database\Seeders;

use App\Models\AccessLog;
use App\Models\College;
use App\Models\Recommendation;
use App\Models\Resource;
use App\Models\SearchLog;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data. Run with:  php artisan migrate:fresh --seed
 *
 * Demo accounts (password for all: password123)
 *   Librarian : LIB-0001
 *   Faculty   : FAC-0001
 *   Student   : 2022-00001
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // 1. Classification: College -> Program -> Subjects
        // ---------------------------------------------------------------
        $structure = [
            ['CCIS', 'College of Computing and Information Sciences', [
                ['BSIT', 'Bachelor of Science in Information Technology', [
                    ['IT 101', 'Introduction to Computing', 1], ['IT 213', 'Web Systems and Technologies', 2],
                    ['IT 312', 'Information Assurance and Security', 3], ['IT 413', 'Capstone Project and Research', 4],
                ]],
                ['BSIS', 'Bachelor of Science in Information Systems', [
                    ['IS 201', 'Systems Analysis and Design', 2], ['IS 305', 'Enterprise Architecture', 3],
                ]],
                ['BSCS', 'Bachelor of Science in Computer Science', [
                    ['CSC 126', 'Computer Vision', 3], ['CSC 211', 'Data Structures and Algorithms', 2],
                ]],
            ]],
            ['CEGS', 'College of Engineering and Geo-Sciences', [
                ['BSCE', 'Bachelor of Science in Civil Engineering', [['CE 211', 'Statics of Rigid Bodies', 2]]],
                ['BSGE', 'Bachelor of Science in Geodetic Engineering', [['GE 101', 'Fundamentals of Surveying', 1]]],
            ]],
            ['CHASS', 'College of Humanities and Social Sciences', [
                ['ABEng', 'Bachelor of Arts in English Language', [['ENG 101', 'Introduction to Linguistics', 1]]],
                ['BSPsych', 'Bachelor of Science in Psychology', [['PSY 101', 'Introduction to Psychology', 1]]],
            ]],
            ['CED', 'College of Education', [
                ['BSEd', 'Bachelor of Secondary Education', [['EDUC 101', 'The Teaching Profession', 1]]],
                ['BEEd', 'Bachelor of Elementary Education', [['EDUC 102', 'Child and Adolescent Development', 1]]],
            ]],
            ['CAA', 'College of Agriculture and Agri-Industries', [
                ['BSA', 'Bachelor of Science in Agriculture', [['AGRI 101', 'Principles of Crop Science', 1]]],
            ]],
            ['COFES', 'College of Forestry and Environmental Science', [
                ['BSF', 'Bachelor of Science in Forestry', [['FOR 101', 'Dendrology', 1]]],
                ['BSES', 'Bachelor of Science in Environmental Science', [['ES 101', 'Environmental Science', 1]]],
            ]],
            ['CMNS', 'College of Mathematics and Natural Sciences', [
                ['BSMath', 'Bachelor of Science in Mathematics', [['MATH 101', 'Calculus I', 1]]],
                ['BSBio', 'Bachelor of Science in Biology', [['BIO 101', 'General Biology', 1]]],
            ]],
        ];

        foreach ($structure as [$code, $name, $programs]) {
            $college = College::create(compact('code', 'name'));
            foreach ($programs as [$pCode, $pName, $subjects]) {
                $program = $college->programs()->create(['code' => $pCode, 'name' => $pName]);
                foreach ($subjects as [$sCode, $sName, $year]) {
                    $program->subjects()->create(['code' => $sCode, 'name' => $sName, 'year_level' => $year, 'min_resources' => 3]);
                }
            }
        }

        $ccis = College::where('code', 'CCIS')->first();

        // ---------------------------------------------------------------
        // 2. Demo accounts
        // ---------------------------------------------------------------
        $librarian = User::create(['name' => 'Maria Librarian', 'id_number' => 'LIB-0001', 'email' => 'librarian@csu.edu.ph',
            'role' => 'librarian', 'password' => 'password123']);
        $faculty = User::create(['name' => 'Juan Dela Cruz', 'id_number' => 'FAC-0001', 'email' => 'faculty@csu.edu.ph',
            'role' => 'faculty', 'college_id' => $ccis->id, 'password' => 'password123']);
        $student = User::create(['name' => 'Ana Santos', 'id_number' => '2022-00001', 'email' => 'student@csu.edu.ph',
            'role' => 'student', 'college_id' => $ccis->id, 'password' => 'password123']);

        $students = collect([$student]);
        foreach (range(2, 8) as $i) {
            $students->push(User::create(['name' => "Student $i", 'id_number' => sprintf('2022-%05d', $i),
                'email' => "student$i@csu.edu.ph", 'role' => 'student', 'college_id' => $ccis->id, 'password' => 'password123']));
        }

        // ---------------------------------------------------------------
        // 3. Sample PDF files copied into PRIVATE storage
        // ---------------------------------------------------------------
        $files = [];
        foreach ([1, 2, 3] as $n) {
            $path = "resources/sample-$n.pdf";
            Storage::disk('local')->put($path, file_get_contents(__DIR__."/files/sample-$n.pdf"));
            $files[] = $path;
        }

        // ---------------------------------------------------------------
        // 4. Resources  [subject code, title, authors, year, type, keywords]
        // ---------------------------------------------------------------
        $resources = [
            ['IT 101', 'Introduction to Computing Concepts', 'R. Reyes', 2023, 'Ebook', 'computing, hardware, software, basics'],
            ['IT 101', 'Computer Fundamentals Workbook', 'L. Garcia', 2016, 'Module', 'computing, basics, office tools'],
            ['IT 213', 'Modern Web Development with Laravel', 'J. Santos', 2025, 'Ebook', 'web, laravel, php, mvc'],
            ['IT 213', 'Responsive Design Patterns', 'K. Lim', 2024, 'Ebook', 'web, css, responsive, ui'],
            ['IT 213', 'Building Secure Web Applications', 'P. Torres', 2022, 'Journal', 'web, security, owasp'],
            ['IT 213', 'HTML and JavaScript Essentials', 'M. Cruz', 2015, 'Ebook', 'web, javascript, html'],
            ['IT 312', 'Information Security Management', 'D. Navarro', 2021, 'Ebook', 'security, risk, policy'],
            ['IT 312', 'Role-Based Access Control in Practice', 'S. Ramos', 2023, 'Research Paper', 'security, rbac, access control'],
            ['IT 413', 'Writing an IT Capstone Manuscript', 'E. Villanueva', 2024, 'Module', 'capstone, research, writing'],
            ['IT 413', 'Digital Library Repository for Academic Institutions', 'H. Panogaling', 2025, 'Thesis', 'repository, digital library, capstone'],
            ['IT 413', 'Usability Evaluation Using ISO 25010', 'F. Mendoza', 2022, 'Research Paper', 'usability, evaluation, iso 25010'],
            ['IS 201', 'Systems Analysis and Design Methods', 'G. Bautista', 2018, 'Ebook', 'sdlc, waterfall, analysis, design'],
            ['IS 201', 'Data Flow Diagrams and ERD Guide', 'N. Aquino', 2023, 'Module', 'erd, dfd, database, design'],
            ['IS 305', 'Enterprise Architecture Frameworks', 'T. Castillo', 2017, 'Ebook', 'architecture, togaf, enterprise'],
            ['CSC 126', 'Research in AI Systems', 'J. Santos', 2025, 'Ebook', 'ai, computer vision, machine learning'],
            ['CSC 126', 'Image Processing Fundamentals', 'B. Flores', 2020, 'Ebook', 'image processing, computer vision'],
            ['CSC 126', 'Object Detection in Philippine Farms', 'C. Dizon', 2024, 'Thesis', 'computer vision, agriculture, ai'],
            ['CSC 211', 'Algorithms Illustrated', 'A. Romero', 2019, 'Ebook', 'algorithms, data structures'],
            ['CSC 211', 'Data Structures in Java', 'V. Ocampo', 2023, 'Ebook', 'data structures, java'],
            ['EDUC 101', 'The Teaching Profession in the Philippines', 'R. Salazar', 2021, 'Ebook', 'education, teaching'],
            ['PSY 101', 'Introduction to Psychology', 'L. Fernandez', 2014, 'Ebook', 'psychology, behavior'],
            ['AGRI 101', 'Crop Science Principles', 'M. Pascual', 2022, 'Ebook', 'agriculture, crops'],
            ['MATH 101', 'Calculus for Beginners', 'J. Rivera', 2020, 'Ebook', 'math, calculus'],
        ];

        $created = collect();
        foreach ($resources as $i => [$subjectCode, $title, $authors, $year, $type, $keywords]) {
            $created->push(Resource::create([
                'subject_id' => Subject::where('code', $subjectCode)->value('id'),
                'uploaded_by' => $librarian->id,
                'title' => $title,
                'authors' => $authors,
                'publication_year' => $year,
                'resource_type' => $type,
                'academic_year' => '2025-2026',
                'description' => "Sample description for \"$title\".",
                'keywords' => $keywords,
                'file_path' => $files[$i % 3],
            ]));
        }

        // ---------------------------------------------------------------
        // 5. Usage history (views + searches over the last 6 months)
        // ---------------------------------------------------------------
        $ccisResources = $created->take(19);
        foreach ($students->push($faculty) as $user) {
            foreach ($ccisResources->random(6) as $resource) {
                foreach (range(1, rand(1, 3)) as $_) {
                    AccessLog::create(['user_id' => $user->id, 'resource_id' => $resource->id,
                        'accessed_at' => now()->subDays(rand(0, 170))->subMinutes(rand(0, 600))]);
                }
            }
        }

        foreach ([['laravel', 2], ['computer vision', 3], ['security', 2], ['capstone', 2],
                  ['blockchain', 0], ['cloud computing', 0], ['mobile app', 0], ['erd', 1]] as [$kw, $count]) {
            foreach (range(1, rand(2, 6)) as $_) {
                SearchLog::create(['user_id' => $students->random()->id, 'keyword' => $kw, 'results_count' => $count,
                    'searched_at' => now()->subDays(rand(0, 150))]);
            }
        }

        // ---------------------------------------------------------------
        // 6. Faculty recommendations in different workflow states
        // ---------------------------------------------------------------
        $recs = [
            ['IT 213', 'Laravel: Up & Running (3rd Edition)', 'Matt Stauffer', "O'Reilly", '3rd', 2023, 'Ebook', 'pending', null],
            ['IT 312', 'Cybersecurity Essentials', 'C. Brooks', 'Sybex', '1st', 2024, 'Ebook', 'pending', null],
            ['CSC 126', 'Deep Learning for Vision Systems', 'M. Elgendy', 'Manning', '1st', 2020, 'Ebook', 'approved', 'Approved. Will acquire this semester.'],
            ['IS 201', 'Old Systems Analysis Textbook', 'Unknown', null, null, 2005, 'Ebook', 'rejected', 'Too outdated. Please recommend a 2019+ edition.'],
            ['IT 413', 'Research Methods for IT Students', 'P. Lee', null, null, 2022, 'Ebook', 'needs_revision', 'Please add the publisher and a link to the source.'],
        ];
        foreach ($recs as [$code, $title, $authors, $publisher, $edition, $year, $type, $status, $remarks]) {
            Recommendation::create([
                'faculty_id' => $faculty->id,
                'subject_id' => Subject::where('code', $code)->value('id'),
                'title' => $title, 'authors' => $authors, 'publisher' => $publisher, 'edition' => $edition,
                'publication_year' => $year, 'resource_type' => $type,
                'reason' => 'Aligned with the course outcomes of '.$code.' and useful for student projects.',
                'status' => $status,
                'librarian_remarks' => $remarks,
                'reviewed_by' => $status === 'pending' ? null : $librarian->id,
                'reviewed_at' => $status === 'pending' ? null : now()->subDays(rand(1, 10)),
            ]);
        }
    }
}
