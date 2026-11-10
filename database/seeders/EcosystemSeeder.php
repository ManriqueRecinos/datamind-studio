<?php

namespace Database\Seeders;

use App\Models\Ecosystem;
use App\Models\EcosystemModule;
use App\Models\EcosystemInterface;
use Illuminate\Database\Seeder;

class EcosystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ecosistema de Configuración & Apariencia
        $settings = Ecosystem::updateOrCreate(
            ['key' => 'settings'],
            [
                'name' => 'Configuración & Apariencia',
                'short_name' => 'Configuración',
                'description' => 'Ajustes de paletas cromáticas, tipografía, modo oscuro y perfil de usuario.',
                'icon' => 'fa-gear',
                'color_preset' => 'indigo',
                'badge_text' => 'MÓDULO 1',
                'route_name' => 'settings.index',
                'order_num' => 1,
                'is_active' => true,
            ]
        );

        $mColors = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $settings->id, 'key' => 'settings_colors'],
            ['name' => 'Paleta y Colores', 'icon' => 'fa-palette', 'order_num' => 1, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mColors->id, 'name' => 'Presets Oficiales'],
            ['icon' => 'fa-swatchbook', 'route_or_action' => '#paletteSection', 'action_type' => 'anchor', 'badge_label' => '5 Temas', 'order_num' => 1]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mColors->id, 'name' => 'Ajuste Hexadecimal'],
            ['icon' => 'fa-eye-dropper', 'route_or_action' => '#paletteSection', 'action_type' => 'anchor', 'badge_label' => null, 'order_num' => 2]
        );

        $mTypography = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $settings->id, 'key' => 'settings_typography'],
            ['name' => 'Tipografía y Escala', 'icon' => 'fa-font', 'order_num' => 2, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mTypography->id, 'name' => 'Fuentes Corporativas'],
            ['icon' => 'fa-text-height', 'route_or_action' => '#typographySection', 'action_type' => 'anchor', 'badge_label' => null, 'order_num' => 1]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mTypography->id, 'name' => 'Tamaño de Texto'],
            ['icon' => 'fa-magnifying-glass-plus', 'route_or_action' => '#fontSizeGrid', 'action_type' => 'anchor', 'badge_label' => null, 'order_num' => 2]
        );

        $mAccount = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $settings->id, 'key' => 'settings_account'],
            ['name' => 'Mi Cuenta y Seguridad', 'icon' => 'fa-user-shield', 'order_num' => 3, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mAccount->id, 'name' => 'Perfil del Usuario'],
            ['icon' => 'fa-id-badge', 'route_or_action' => '#profileSection', 'action_type' => 'anchor', 'badge_label' => null, 'order_num' => 1]
        );

        // 2. Ecosistema de Chat & Analítica IA
        $chat = Ecosystem::updateOrCreate(
            ['key' => 'chat'],
            [
                'name' => 'Chat & Analítica Inteligente',
                'short_name' => 'Chat IA',
                'description' => 'Consultas en lenguaje natural, Text-to-SQL, gráficos interactivos y entrenamiento RAG.',
                'icon' => 'fa-comments',
                'color_preset' => 'ocean',
                'badge_text' => 'MÓDULO 2',
                'route_name' => 'chat.index',
                'order_num' => 2,
                'is_active' => true,
            ]
        );

        $mChatOps = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $chat->id, 'key' => 'chat_operations'],
            ['name' => 'Consola Conversacional', 'icon' => 'fa-message', 'order_num' => 1, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mChatOps->id, 'name' => 'Nueva Conversación'],
            ['icon' => 'fa-plus', 'route_or_action' => '#btnNewChat', 'action_type' => 'trigger', 'badge_label' => 'Directo', 'order_num' => 1]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mChatOps->id, 'name' => 'Centro de Entrenamiento IA'],
            ['icon' => 'fa-graduation-cap', 'route_or_action' => '#btnOpenTrainingCenter', 'action_type' => 'modal', 'badge_label' => '5 Niveles', 'order_num' => 2]
        );

        $mHistory = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $chat->id, 'key' => 'chat_history'],
            ['name' => 'Historial de Sesiones', 'icon' => 'fa-clock-rotate-left', 'order_num' => 2, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mHistory->id, 'name' => 'Mis Conversaciones'],
            ['icon' => 'fa-comments', 'route_or_action' => '#userSessionsList', 'action_type' => 'anchor', 'badge_label' => null, 'order_num' => 1]
        );

        // 3. Ecosistema de Vectorización & Bases de Datos
        $vectorization = Ecosystem::updateOrCreate(
            ['key' => 'vectorization'],
            [
                'name' => 'Vectorización & Bases de Datos',
                'short_name' => 'Vectorización',
                'description' => 'Conector multi-motor PostgreSQL/MariaDB, exploración de esquemas y embeddings con pgvector.',
                'icon' => 'fa-database',
                'color_preset' => 'emerald',
                'badge_text' => 'MÓDULO 3',
                'route_name' => 'connections.index',
                'order_num' => 3,
                'is_active' => true,
            ]
        );

        $mDbConnect = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $vectorization->id, 'key' => 'db_connection_ops'],
            ['name' => 'Gestor de Conexiones', 'icon' => 'fa-plug-circle-bolt', 'order_num' => 1, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mDbConnect->id, 'name' => 'Nueva Conexión DB'],
            ['icon' => 'fa-plus', 'route_or_action' => '#connectionForm', 'action_type' => 'anchor', 'badge_label' => 'Test SQL', 'order_num' => 1]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mDbConnect->id, 'name' => 'Bases Guardadas'],
            ['icon' => 'fa-server', 'route_or_action' => '#connectionsContainer', 'action_type' => 'anchor', 'badge_label' => null, 'order_num' => 2]
        );

        $mTableExplorer = EcosystemModule::updateOrCreate(
            ['ecosystem_id' => $vectorization->id, 'key' => 'table_explorer_ops'],
            ['name' => 'Explorador y pgvector', 'icon' => 'fa-brain', 'order_num' => 2, 'is_active' => true]
        );
        EcosystemInterface::updateOrCreate(
            ['module_id' => $mTableExplorer->id, 'name' => 'Esquemas y Tablas'],
            ['icon' => 'fa-table', 'route_or_action' => '#tableExplorerPanel', 'action_type' => 'anchor', 'badge_label' => '768-dim', 'order_num' => 1]
        );
    }
}
