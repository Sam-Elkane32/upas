<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Campuses must exist before FK checks (e.g. SQLite truncate/delete) and before campus_admins.
        if (!Schema::hasTable('campuses')) {
            Schema::create('campuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // First, let's clean up existing data
        DB::table('users')->truncate();
        DB::table('accomplishment_plans')->truncate();
        DB::table('quarterly_reports')->truncate();
        DB::table('strategic_goals')->truncate();
        DB::table('key_performance_indicators')->truncate();
        
        // Update users table with new role system
        Schema::table('users', function (Blueprint $table) {
            // Check if role column exists and update it
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Add new role system
            $table->enum('role', ['super_admin', 'admin', 'creator_editor'])->default('creator_editor')->after('position');
            
            // Add campus support if not exists
            if (!Schema::hasColumn('users', 'campus')) {
                $table->string('campus')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'campus_code')) {
                $table->string('campus_code')->nullable()->after('campus');
            }
            
            // Add approval workflow fields
            if (!Schema::hasColumn('users', 'is_approved')) {
                $table->boolean('is_approved')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('is_approved');
            }
            if (!Schema::hasColumn('users', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->after('approved_at');
            }
        });

        // Create campus admins table if it doesn't exist
        if (!Schema::hasTable('campus_admins')) {
            Schema::create('campus_admins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campus_id')->constrained('campuses');
                $table->foreignId('admin_user_id')->constrained('users');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                
                $table->unique(['campus_id', 'admin_user_id']);
            });
        }

        // Insert PSU campuses
        $campuses = [
            ['name' => 'PSU Lingayen', 'code' => 'LING', 'location' => 'Lingayen, Pangasinan', 'description' => 'Main Campus'],
            ['name' => 'PSU Alaminos', 'code' => 'ALAM', 'location' => 'Alaminos City, Pangasinan', 'description' => 'Alaminos Campus'],
            ['name' => 'PSU Urdaneta', 'code' => 'URDA', 'location' => 'Urdaneta City, Pangasinan', 'description' => 'Urdaneta Campus'],
            ['name' => 'PSU Bayambang', 'code' => 'BAYA', 'location' => 'Bayambang, Pangasinan', 'description' => 'Bayambang Campus'],
            ['name' => 'PSU Infanta', 'code' => 'INFA', 'location' => 'Infanta, Pangasinan', 'description' => 'Infanta Campus'],
            ['name' => 'PSU San Carlos', 'code' => 'SANC', 'location' => 'San Carlos City, Pangasinan', 'description' => 'San Carlos Campus'],
            ['name' => 'PSU Asingan', 'code' => 'ASIN', 'location' => 'Asingan, Pangasinan', 'description' => 'Asingan Campus'],
            ['name' => 'PSU Binmaley', 'code' => 'BINM', 'location' => 'Binmaley, Pangasinan', 'description' => 'Binmaley Campus'],
            ['name' => 'PSU Sta. Maria', 'code' => 'STAM', 'location' => 'Sta. Maria, Pangasinan', 'description' => 'Sta. Maria Campus'],
        ];

        foreach ($campuses as $campus) {
            DB::table('campuses')->updateOrInsert(
                ['code' => $campus['code']],
                array_merge($campus, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create the new user hierarchy
        $users = [
            // Super Admin
            [
                'name' => 'UAPS Super Administrator',
                'email' => 'superadmin@psu.edu.ph',
                'password' => bcrypt('UAPS@2025!'),
                'employee_id' => 'PSU-SA-001',
                'department' => 'Office of the President',
                'position' => 'System Administrator',
                'role' => 'super_admin',
                'campus' => 'All Campuses',
                'campus_code' => 'ALL',
                'phone_number' => '+63-999-000-0001',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Campus Admins (2-3 per campus)
            [
                'name' => 'Dr. Maria Santos',
                'email' => 'admin.lingayen@psu.edu.ph',
                'password' => bcrypt('Admin@2025!'),
                'employee_id' => 'PSU-LING-ADM-001',
                'department' => 'Campus Executive Office',
                'position' => 'Campus Executive Director',
                'role' => 'admin',
                'campus' => 'PSU Lingayen',
                'campus_code' => 'LING',
                'phone_number' => '+63-999-000-0002',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dr. Juan Dela Cruz',
                'email' => 'admin.alaminos@psu.edu.ph',
                'password' => bcrypt('Admin@2025!'),
                'employee_id' => 'PSU-ALAM-ADM-001',
                'department' => 'Campus Executive Office',
                'position' => 'Campus Executive Director',
                'role' => 'admin',
                'campus' => 'PSU Alaminos',
                'campus_code' => 'ALAM',
                'phone_number' => '+63-999-000-0003',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dr. Ana Garcia',
                'email' => 'admin.urdaneta@psu.edu.ph',
                'password' => bcrypt('Admin@2025!'),
                'employee_id' => 'PSU-URDA-ADM-001',
                'department' => 'Campus Executive Office',
                'position' => 'Campus Executive Director',
                'role' => 'admin',
                'campus' => 'PSU Urdaneta',
                'campus_code' => 'URDA',
                'phone_number' => '+63-999-000-0004',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Creator/Editors (2-3 per campus)
            [
                'name' => 'Prof. Carlos Mendoza',
                'email' => 'creator.lingayen@psu.edu.ph',
                'password' => bcrypt('Creator@2025!'),
                'employee_id' => 'PSU-LING-CRE-001',
                'department' => 'Academic Affairs',
                'position' => 'Planning Officer',
                'role' => 'creator_editor',
                'campus' => 'PSU Lingayen',
                'campus_code' => 'LING',
                'phone_number' => '+63-999-000-0005',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Prof. Elena Rodriguez',
                'email' => 'creator.alaminos@psu.edu.ph',
                'password' => bcrypt('Creator@2025!'),
                'employee_id' => 'PSU-ALAM-CRE-001',
                'department' => 'Academic Affairs',
                'position' => 'Planning Officer',
                'role' => 'creator_editor',
                'campus' => 'PSU Alaminos',
                'campus_code' => 'ALAM',
                'phone_number' => '+63-999-000-0006',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Prof. Miguel Torres',
                'email' => 'creator.urdaneta@psu.edu.ph',
                'password' => bcrypt('Creator@2025!'),
                'employee_id' => 'PSU-URDA-CRE-001',
                'department' => 'Academic Affairs',
                'position' => 'Planning Officer',
                'role' => 'creator_editor',
                'campus' => 'PSU Urdaneta',
                'campus_code' => 'URDA',
                'phone_number' => '+63-999-000-0007',
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }

        // Assign campus admins
        $campusAdmins = [
            ['campus_id' => 1, 'admin_user_id' => 2, 'is_primary' => true], // Lingayen
            ['campus_id' => 2, 'admin_user_id' => 3, 'is_primary' => true], // Alaminos
            ['campus_id' => 3, 'admin_user_id' => 4, 'is_primary' => true], // Urdaneta
        ];

        foreach ($campusAdmins as $admin) {
            DB::table('campus_admins')->insert(array_merge($admin, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_admins');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'campus',
                'campus_code',
                'is_approved',
                'approved_at',
                'approved_by'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['faculty', 'department_head', 'admin', 'staff'])->default('faculty')->after('position');
        });
    }
};