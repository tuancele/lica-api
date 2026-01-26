<?php

declare(strict_types=1);
use Illuminate\Database\Seeder;
use App\Modules\Permission\Models\Permission;

class MarketingPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Parent Permission 'marketing' if not exists
        $marketing = Permission::where('slug', 'marketing')->first();
        if (!$marketing) {
            $marketing = new Permission();
            $marketing->name = 'Kênh Marketing';
            $marketing->slug = 'marketing';
            $marketing->parent_id = 0;
            $marketing->sort = 99; // Adjust sort order as needed
            $marketing->status = 1;
            $marketing->created_at = now();
            $marketing->save();
        }

        // Create Child Permission 'campaign'
        $campaign = Permission::where('slug', 'campaign')->where('parent_id', $marketing->id)->first();
        if (!$campaign) {
            $campaign = new Permission();
            $campaign->name = 'Chương trình khuyến mại';
            $campaign->slug = 'campaign';
            $campaign->parent_id = $marketing->id;
            $campaign->sort = 1;
            $campaign->status = 1;
            $campaign->created_at = now();
            $campaign->save();
        }
        
        // Assign to Admin Role (assuming Role 1 is Admin, or we need to find it)
        // This part depends on RolePermission structure.
        // Assuming 'role_permission' table.
        $adminRole = \App\Modules\Role\Models\Role::find(1); // Standard admin id
        if($adminRole){
            // Attach if not exists
            if(!$adminRole->permissions->contains($marketing->id)){
                $adminRole->permissions()->attach($marketing->id);
            }
            if(!$adminRole->permissions->contains($campaign->id)){
                $adminRole->permissions()->attach($campaign->id);
            }
        }
    }
}
