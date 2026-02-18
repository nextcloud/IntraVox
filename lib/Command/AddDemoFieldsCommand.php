<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Add account fields to demo users for testing the People widget
 *
 * This command:
 * 1. Sets standard Nextcloud account properties (role, organisation, headline, etc.)
 *    from testdata/people/demo-users.json via IAccountManager
 * 2. Adds custom fields (employeeId, costCenter, etc.) to user preferences
 *    for testing LDAP/OIDC custom field display
 *
 * Usage: occ intravox:add-demo-fields
 */
class AddDemoFieldsCommand extends Command {
    private const APP_ID = 'intravox';
    private const CUSTOM_FIELDS_KEY = 'custom_fields';

    // Map JSON keys to IAccountManager properties
    private const PROPERTY_MAP = [
        'role' => IAccountManager::PROPERTY_ROLE,
        'organisation' => IAccountManager::PROPERTY_ORGANISATION,
        'headline' => IAccountManager::PROPERTY_HEADLINE,
        'biography' => IAccountManager::PROPERTY_BIOGRAPHY,
        'phone' => IAccountManager::PROPERTY_PHONE,
        'address' => IAccountManager::PROPERTY_ADDRESS,
        'website' => IAccountManager::PROPERTY_WEBSITE,
        'twitter' => IAccountManager::PROPERTY_TWITTER,
        'fediverse' => IAccountManager::PROPERTY_FEDIVERSE,
        'birthdate' => IAccountManager::PROPERTY_BIRTHDATE,
    ];

    public function __construct(
        private IUserManager $userManager,
        private IAccountManager $accountManager,
        private IConfig $config
    ) {
        parent::__construct();
    }

    protected function configure(): void {
        $this->setName('intravox:add-demo-fields')
            ->setDescription('Add account fields to demo users for testing People widget filters and custom fields')
            ->addOption(
                'standard-only',
                's',
                InputOption::VALUE_NONE,
                'Only set standard Nextcloud properties (role, organisation, etc.), skip custom fields'
            )
            ->addOption(
                'custom-only',
                'c',
                InputOption::VALUE_NONE,
                'Only set custom fields (employeeId, costCenter, etc.), skip standard properties'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $standardOnly = $input->getOption('standard-only');
        $customOnly = $input->getOption('custom-only');

        $output->writeln('<info>IntraVox Demo Fields Setup</info>');
        $output->writeln('<info>=========================</info>');
        $output->writeln('');

        $standardCount = 0;
        $customCount = 0;

        // Part 1: Set standard Nextcloud account properties from demo-users.json
        if (!$customOnly) {
            $output->writeln('<info>Part 1: Setting standard Nextcloud account properties...</info>');
            $output->writeln('');
            $standardCount = $this->setStandardProperties($output);
        }

        // Part 2: Add custom fields to selected users
        if (!$standardOnly) {
            $output->writeln('');
            $output->writeln('<info>Part 2: Adding custom fields for LDAP/OIDC testing...</info>');
            $output->writeln('');
            $customCount = $this->setCustomFields($output);
        }

        $output->writeln('');
        $output->writeln('========================================');
        if (!$customOnly) {
            $output->writeln("<info>✓ Set standard properties for $standardCount users</info>");
        }
        if (!$standardOnly) {
            $output->writeln("<info>✓ Added custom fields to $customCount users</info>");
        }
        $output->writeln('');
        $output->writeln('To test in the People widget:');
        $output->writeln('1. Create a filter: Role contains "Manager"');
        $output->writeln('2. Create a filter: Organisation equals "IT"');
        $output->writeln('3. Enable "Show custom fields" to see employeeId, costCenter, etc.');
        $output->writeln('========================================');

        return 0;
    }

    /**
     * Set standard Nextcloud account properties from demo-users.json
     */
    private function setStandardProperties(OutputInterface $output): int {
        // Load demo users data
        $demoUsersPath = __DIR__ . '/../../testdata/people/demo-users.json';

        // Try alternative path if not found
        if (!file_exists($demoUsersPath)) {
            $demoUsersPath = dirname(__DIR__, 2) . '/testdata/people/demo-users.json';
        }

        if (!file_exists($demoUsersPath)) {
            $output->writeln('<error>Demo users file not found at: ' . $demoUsersPath . '</error>');
            return 0;
        }

        $demoUsersJson = file_get_contents($demoUsersPath);
        $demoUsers = json_decode($demoUsersJson, true);

        if (!is_array($demoUsers)) {
            $output->writeln('<error>Failed to parse demo users JSON</error>');
            return 0;
        }

        $successCount = 0;

        foreach ($demoUsers as $userData) {
            $userId = $userData['id'] ?? null;
            if (!$userId) {
                continue;
            }

            $user = $this->userManager->get($userId);
            if ($user === null) {
                // User doesn't exist, skip silently (they may not have been created yet)
                continue;
            }

            try {
                $account = $this->accountManager->getAccount($user);
                $updated = false;
                $updatedFields = [];

                foreach (self::PROPERTY_MAP as $jsonKey => $accountProperty) {
                    if (isset($userData[$jsonKey]) && !empty($userData[$jsonKey])) {
                        try {
                            $property = $account->getProperty($accountProperty);
                            $currentValue = $property->getValue();

                            // Only update if value is different or empty
                            if ($currentValue !== $userData[$jsonKey]) {
                                $property->setValue($userData[$jsonKey]);
                                $updated = true;
                                $updatedFields[] = $jsonKey;
                            }
                        } catch (\Exception $e) {
                            // Property might not exist, try to create it
                            $output->writeln("<comment>  Could not set $jsonKey: " . $e->getMessage() . "</comment>");
                        }
                    }
                }

                if ($updated) {
                    $this->accountManager->updateAccount($account);
                    $output->writeln("<info>✓ {$userId} ({$user->getDisplayName()}):</info>");
                    foreach ($updatedFields as $field) {
                        $output->writeln("  - $field: " . $userData[$field]);
                    }
                    $successCount++;
                }

            } catch (\Exception $e) {
                $output->writeln("<error>Error updating $userId: " . $e->getMessage() . "</error>");
            }
        }

        if ($successCount === 0) {
            $output->writeln('<comment>No users needed updating (properties already set or users not found)</comment>');
        }

        return $successCount;
    }

    /**
     * Set custom fields (simulating LDAP/OIDC) for selected demo users
     */
    private function setCustomFields(OutputInterface $output): int {
        // Custom field data simulating Keycloak/OIDC claims
        $customData = [
            'demo001' => [
                'employeeId' => 'EMP-001',
                'costCenter' => 'CC-100',
                'officeLocation' => 'Amsterdam HQ',
                'employeeType' => 'Full-time',
            ],
            'demo002' => [
                'employeeId' => 'EMP-002',
                'costCenter' => 'CC-100',
                'officeLocation' => 'Amsterdam HQ',
                'employeeType' => 'Full-time',
            ],
            'demo003' => [
                'employeeId' => 'EMP-003',
                'costCenter' => 'CC-200',
                'officeLocation' => 'Rotterdam Office',
                'employeeType' => 'Part-time',
            ],
            'demo038' => [ // Bram van der Meer
                'employeeId' => 'EMP-038',
                'costCenter' => 'CC-400',
                'officeLocation' => 'Sales Division',
                'employeeType' => 'Full-time',
                'startDate' => '2021-03-15',
                'contractType' => 'Permanent',
            ],
            'demo010' => [
                'employeeId' => 'EMP-010',
                'costCenter' => 'CC-100',
                'officeLocation' => 'Amsterdam HQ',
                'employeeType' => 'Contractor',
            ],
            'demo068' => [ // Andrea Conti - Budget Analyst
                'employeeId' => 'EMP-068',
                'costCenter' => 'CC-300',
                'officeLocation' => 'Amsterdam HQ',
                'employeeType' => 'Full-time',
            ],
        ];

        $successCount = 0;

        foreach ($customData as $userId => $fields) {
            $user = $this->userManager->get($userId);

            if ($user === null) {
                $output->writeln("<comment>⚠ User $userId not found, skipping</comment>");
                continue;
            }

            $output->writeln("<info>✓ $userId ({$user->getDisplayName()}):</info>");

            try {
                // Store custom fields as JSON in user preferences
                $existingJson = $this->config->getUserValue($userId, self::APP_ID, self::CUSTOM_FIELDS_KEY, '{}');
                $existingFields = json_decode($existingJson, true) ?: [];

                // Merge with new fields
                $mergedFields = array_merge($existingFields, $fields);

                // Save to user preferences
                $this->config->setUserValue(
                    $userId,
                    self::APP_ID,
                    self::CUSTOM_FIELDS_KEY,
                    json_encode($mergedFields)
                );

                foreach ($fields as $key => $value) {
                    $output->writeln("  - $key: $value");
                }

                $successCount++;

            } catch (\Exception $e) {
                $output->writeln("<error>  Error: " . $e->getMessage() . "</error>");
            }

            $output->writeln('');
        }

        return $successCount;
    }
}
