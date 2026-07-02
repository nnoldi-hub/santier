<?php

namespace App\Support;

class IamLabels
{
    /**
     * Return a user-friendly Romanian label for a role name.
     */
    public static function roleLabel(string $roleName): string
    {
        $map = [
            'superadmin' => 'Superadmin platforma',
            'tenant_admin' => 'Administrator firma',
            'data_entry' => 'Operator introducere date',
            'quote_specialist' => 'Specialist oferte',
            'site_manager' => 'Manager proiect / Sef santier',
            'finance' => 'Financiar',
            'auditor' => 'Auditor (doar vizualizare)',
            'client_portal' => 'Portal client (extern)',
            'subcontractor_portal' => 'Portal subcontractor (extern)',
        ];

        if (isset($map[$roleName])) {
            return $map[$roleName];
        }

        return ucfirst(str_replace('_', ' ', $roleName));
    }

    /**
     * Return a user-friendly Romanian label for a permission key.
     */
    public static function permissionLabel(string $permissionName): string
    {
        $parts = explode('.', $permissionName, 2);
        if (count($parts) !== 2) {
            return ucfirst(str_replace('_', ' ', $permissionName));
        }

        [$module, $action] = $parts;

        $moduleLabels = [
            'quotes' => 'Oferte / Devize',
            'projects' => 'Proiecte',
            'tasks' => 'Taskuri',
            'calendar' => 'Calendar',
            'documents' => 'Documente',
            'finance' => 'Financiar',
            'ai_tools' => 'Instrumente AI',
            'company_settings' => 'Setari firma',
            'users' => 'Utilizatori',
            'reports' => 'Rapoarte',
            'contractors' => 'Subcontractori',
            'equipment' => 'Utilaje',
        ];

        $actionLabels = [
            'view' => 'Vizualizare',
            'view_limited' => 'Vizualizare limitata',
            'create' => 'Creare',
            'edit' => 'Editare',
            'delete' => 'Stergere',
            'export' => 'Export',
            'approve' => 'Aprobare',
        ];

        $moduleLabel = $moduleLabels[$module] ?? ucfirst(str_replace('_', ' ', $module));
        $actionLabel = $actionLabels[$action] ?? ucfirst(str_replace('_', ' ', $action));

        return $moduleLabel . ' - ' . $actionLabel;
    }
}
