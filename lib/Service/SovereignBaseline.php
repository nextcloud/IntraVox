<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

/**
 * Deterministic English portal seed. No runtime credentials or claimed live
 * operational status. Existing content is replaced only by explicit provisioning.
 */
final class SovereignBaseline {
    public const HOME_ID = 'page-home-en';
    public const NASSAU_ID = 'page-a1000002-0002-4000-8000-000000000021';
    public const ATHENS_ID = 'page-a1000002-0002-4000-8000-000000000022';
    public const COMPLIANCE = 'EU GDPR • NIS2 • ISO/IEC 27001:2022 • ISO/IEC 20000-1 • SOC 2 Type II • CSA STAR';
    public const EXECUTIVE_UIDS = ['scott.mackenzie', 'victor.kovacs', 'alfredo.carrasco', 'stelios.xeroudakis'];
    public const FINAL_UX_PATHS = [
        'home.json', 'sovereign-news/group/group.json',
        'sovereign-news/caribbean/caribbean.json', 'sovereign-news/emea/emea.json',
    ];

    private function widget(string $type, array $data, int $column = 1, int $order = 0): array {
        return ['type' => $type, 'column' => $column, 'order' => $order] + $data;
    }

    private function heading(string $text, int $column = 1): array {
        return $this->widget('heading', ['content' => $text, 'level' => 2], $column);
    }

    private function link(string $title, string $id, string $icon = 'briefcase', string $entity = 'all'): array {
        return ['title' => $title, 'text' => '', 'url' => '#' . $id, 'uniqueId' => $id,
            'icon' => $icon, 'entity' => $entity, 'excomOnly' => $entity === 'excom', 'target' => '_self'];
    }

    private function app(string $title, string $appId, string $icon, string $text = ''): array {
        return ['title' => $title, 'text' => $text, 'url' => '/index.php/apps/' . $appId . '/',
            'icon' => $icon, 'appId' => $appId, 'entity' => 'all', 'excomOnly' => false, 'target' => '_self'];
    }

    public function homePage(string $homeId = self::HOME_ID): array {
        $launchpad = [
            $this->app('Team Vaults (1–8)', 'files', 'folder', 'Department libraries & MetaVox • SharePoint alternative'),
            $this->app('Mail', 'mail', 'email', 'Sovereign inbox • Outlook alternative'),
            $this->app('Calendar & Contacts', 'calendar', 'calendar', 'Shared schedules & directory • Exchange alternative'),
            $this->app('Nextcloud Talk', 'spreed', 'chat', 'Encrypted chat, channels & calls • Teams alternative'),
            $this->app('Collectives', 'collectives', 'book-open', 'Runbooks, SOPs & team wikis • OneNote alternative'),
            $this->app('Deck', 'deck', 'view-column', 'Kanban project & PMO boards • Planner alternative'),
            $this->app('Tables & Forms', 'tables', 'table-large', 'Structured trackers & intake surveys • Lists/Forms alternative')
                + ['fallbackAppId' => 'forms', 'fallbackUrl' => '/index.php/apps/forms/'],
            ['title' => 'Global Search', 'text' => 'Unified files, pages & messages • Cmd/Ctrl+K',
                'url' => '#agl-global-search', 'icon' => 'web', 'action' => 'global-search', 'entity' => 'all'],
        ];
        $offices = [
            $this->link('COO — Commercial & Ops', 'page-sovereign-commercial', 'briefcase'),
            $this->link('Caribbean — Nassau C3', self::NASSAU_ID, 'cloud', 'cloud-carib'),
            $this->link('EMEA — Athens C3', self::ATHENS_ID, 'cloud', 'athena-cloud'),
            $this->link('CFO — Finance & Treasury', 'page-sovereign-finance', 'chart-line'),
            $this->link('CTO — Technology & Product', 'page-sovereign-technology', 'lightbulb-on'),
            $this->link('CEO — Governance & CISO', 'page-sovereign-governance', 'shield-check'),
            $this->link('ExCom HoldCo Vault', 'page-sovereign-excom', 'shield-account', 'excom'),
            ['title' => 'Executive Matrix & Directory', 'url' => '#organogram-matrix',
                'icon' => 'account-group', 'action' => 'organogram-matrix', 'entity' => 'excom',
                'excomOnly' => true, 'target' => '_self'],
        ];
        $officeDescriptions = [
            'Sales, Marketing, Solutions, PMO/Bids & HR',
            '24x7x365 NOC / SOC / EMC & Service Delivery',
            '24x7x365 NOC / SOC / EMC & Sovereign Cloud',
            'Accounting, Controller, Treasury & FP&A',
            'Internal Systems [ISD] & Sovereign Portfolio',
            'General Counsel, Contracts, ISO 27001 & NIS2',
            'Restricted HoldCo resolutions & board packs',
            'Jump to Group Organogram & ExCom charter',
        ];
        foreach ($offices as $index => &$office) {
            $office['text'] = $officeDescriptions[$index];
        }
        unset($office);
        return [
            'uniqueId' => $homeId, 'title' => 'Athena Group — Sovereign 365', 'status' => 'published',
            'hidePageTitleBar' => true, 'disableSocial' => true, 'entity' => 'all', 'excomOnly' => false,
            'layout' => ['columns' => 1, 'rows' => [
                ['id' => 'sovereign-overview', 'columns' => 1, 'widgets' => [
                    $this->heading('Sovereign 365'),
                    $this->widget('text', ['content' => "**Security. Sovereignty. Scale.**\n\nOne executive workspace. Two Command & Control Centres. Shared authority, local sovereignty."], 1, 1),
                    $this->widget('news', ['title' => 'Featured announcements', 'sourcePath' => 'sovereign-news',
                        'layout' => 'grid', 'columns' => 3, 'limit' => 3, 'sortBy' => 'title', 'sortOrder' => 'asc',
                        'showDate' => false, 'showImage' => false, 'showExcerpt' => true, 'excerptLength' => 150], 1, 2),
                ]],
                ['id' => 'sovereign-workspaces', 'columns' => 2, 'widgets' => [
                    $this->heading('Sovereign 365 App Launchpad'),
                    $this->widget('links', ['layout' => 'tiles', 'columns' => 2, 'items' => $launchpad], 1, 1),
                    $this->heading('Executive Offices & Dual-C3 Hubs', 2),
                    $this->widget('links', ['layout' => 'tiles', 'columns' => 2, 'items' => $offices], 2, 1),
                ]],
                ['id' => 'sovereign-matrix', 'columns' => 1, 'widgets' => [
                    $this->widget('organogram', ['id' => 'sovereign-governance-matrix',
                        'title' => 'Group Governance & Executive Matrix', 'defaultExpanded' => true, 'showCompanies' => true]),
                ]],
            ]],
        ];
    }

    /** @return array<string, array> Path within the English language folder. */
    public function documents(string $homeId = self::HOME_ID): array {
        $nodes = [
            'commercial' => ['Commercial & Ops', 'all', ['Sales & Marketing', 'Solutions & Sales Ops', 'Commercial Delivery PMO / Bids', 'Administration & HR']],
            'cloud-carib-commercial' => ['Cloud Carib Commercial Hub', 'cloud-carib', ['Caribbean commercial coordination']],
            'athena-cloud-commercial' => ['Athena Cloud Commercial Hub', 'athena-cloud', ['EMEA commercial coordination']],
            'engineering' => ['Engineering & C3 — Global Matrix', 'all', ['Nassau C3 & Athens C3', 'Acting CTO — Technical', 'Acting CEO — Operational']],
            'finance' => ['Finance & Treasury', 'all', ['Accounting / Controller', 'Treasury', 'FP&A']],
            'technology' => ['Technology & Product', 'all', ['ISD', 'Product Portfolio']],
            'governance' => ['Governance & Legal', 'all', ['General Counsel / Contracts', 'CISO', 'ISO 27001 / SOC 2 / NIS2']],
            'excom' => ['ExCom HoldCo Governance', 'excom', ['Executive governance workspace', 'Access is managed by Nextcloud folder permissions.']],
            'nassau' => ['Caribbean — Nassau C3', 'cloud-carib', ['Cloud Carib', 'Acting CTO — Technical', 'Acting CEO — Operational']],
            'athens' => ['EMEA — Athens C3', 'athena-cloud', ['Athena Cloud', 'Acting CTO — Technical', 'Acting CEO — Operational']],
        ];
        $ids = ['nassau' => self::NASSAU_ID, 'athens' => self::ATHENS_ID];
        $docs = ['home.json' => $this->homePage($homeId)];
        foreach ($nodes as $slug => [$title, $entity, $functions]) {
            $folder = 'sovereign-' . $slug;
            $id = $ids[$slug] ?? 'page-sovereign-' . $slug;
            $docs[$folder . '/' . $folder . '.json'] = [
                'uniqueId' => $id, 'title' => $title, 'hidePageTitleBar' => true,
                'disableSocial' => true, 'status' => 'published', 'entity' => $entity, 'excomOnly' => $entity === 'excom',
                'layout' => ['columns' => 1, 'rows' => [['columns' => 1, 'widgets' => [
                    $this->heading($title),
                    $this->widget('text', ['content' => "**Sovereign 365 workspace baseline**\n\n"
                        . implode("\n", array_map(static fn(string $s): string => '- ' . $s, $functions))
                        . "\n\nPrototype workspace: operational content and evidence are maintained by the responsible office. No live service status is asserted."], 1, 1),
                    $this->widget('links', ['columns' => 2, 'layout' => 'tiles',
                        'items' => [$this->link('Sovereign 365 Home', self::HOME_ID, 'home')]], 1, 2),
                    ...($slug === 'engineering' ? [$this->widget('organogram', ['title' => 'Global Executive Matrix',
                        'defaultExpanded' => true, 'showCompanies' => true], 1, 3)] : []),
                ]]]],
            ];
        }
        $news = [
            'group' => ['01 — Sovereign 365 Workspace Cutover Guide', 'all', 'Unified single pane of glass replacing legacy Microsoft 365 workflows across Team Vaults, Stalwart Mail, Talk, Collectives, and Deck.'],
            'caribbean' => ['02 — Nassau C3 Operational Readiness & Runbooks', 'cloud-carib', '24x7x365 Caribbean NOC, SOC, and EMC standard operating procedures, shift handover templates, and escalation paths.'],
            'emea' => ['03 — Athens C3 EMEA Sovereign Cloud Operations', 'athena-cloud', 'European Sovereign Cloud architecture blueprints, VCF 9 & OpenStack runbooks, and NIS2 / ISO 27001 control registries.'],
        ];
        foreach ($news as $slug => [$title, $entity, $body]) {
            $docs["sovereign-news/$slug/$slug.json"] = [
                'uniqueId' => 'page-sovereign-news-' . $slug, 'title' => $title, 'status' => 'published',
                'entity' => $entity, 'excomOnly' => false, 'disableSocial' => true,
                'layout' => ['columns' => 1, 'rows' => [['columns' => 1, 'widgets' => [
                    $this->heading($title), $this->widget('text', ['content' => $body], 1, 1),
                ]]]],
            ];
        }
        $docs['navigation.json'] = ['type' => 'megamenu', 'items' => [
            $this->nav('Home', self::HOME_ID),
            $this->nav('Commercial & Ops', 'page-sovereign-commercial', [
                $this->nav('Office of the COO', 'page-sovereign-commercial',
                    array_map(fn(string $s): array => $this->nav($s, 'page-sovereign-commercial'), $nodes['commercial'][2])),
                $this->nav('Cloud Carib', 'page-sovereign-cloud-carib-commercial', [], 'cloud-carib'),
                $this->nav('Athena Cloud', 'page-sovereign-athena-cloud-commercial', [], 'athena-cloud'),
            ]),
            $this->nav('Engineering & C3', 'page-sovereign-engineering', [
                $this->nav('Global Matrix', 'page-sovereign-engineering'),
                $this->nav('Caribbean — Nassau C3', self::NASSAU_ID, [], 'cloud-carib'),
                $this->nav('EMEA — Athens C3', self::ATHENS_ID, [], 'athena-cloud'),
            ]),
            $this->nav('Finance & Treasury', 'page-sovereign-finance', [
                $this->nav('Office of the CFO', 'page-sovereign-finance', array_map(fn(string $s): array => $this->nav($s, 'page-sovereign-finance'), $nodes['finance'][2])),
            ]),
            $this->nav('Technology & Product', 'page-sovereign-technology', [
                $this->nav('Office of the CTO', 'page-sovereign-technology', array_map(fn(string $s): array => $this->nav($s, 'page-sovereign-technology'), $nodes['technology'][2])),
            ]),
            $this->nav('Governance & Legal', 'page-sovereign-governance', [
                $this->nav('Office of the CEO', 'page-sovereign-governance',
                    array_map(fn(string $s): array => $this->nav($s, 'page-sovereign-governance'), $nodes['governance'][2])),
                $this->nav('ExCom HoldCo Governance', 'page-sovereign-excom', [], 'excom'),
            ]),
            ['id' => 'sovereign-apps', 'title' => 'Sovereign 365 Apps', 'entity' => 'all', 'children' => array_map(
                fn(array $a): array => ['id' => 'app-' . $a[1], 'title' => $a[0], 'url' => '/index.php/apps/' . $a[1] . '/', 'appId' => $a[1], 'entity' => 'all'],
                [['Team Vaults', 'files'], ['Mail', 'mail'], ['Calendar', 'calendar'], ['Talk', 'spreed'], ['Collectives', 'collectives'], ['Deck', 'deck']]
            )],
        ]];
        $docs['footer.json'] = ['content' => "**Athena Group — Sovereign 365**\n\nCompliance baseline: " . self::COMPLIANCE];
        return $docs;
    }

    private function nav(string $title, string $id, array $children = [], string $entity = 'all'): array {
        return ['id' => 'nav-' . substr(sha1($title), 0, 12), 'title' => $title, 'uniqueId' => $id,
            'entity' => $entity, 'excomOnly' => $entity === 'excom', 'children' => $children];
    }
}
