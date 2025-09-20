<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/plain');
echo "Checking reports table...\n";

try {
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'bus_reports'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Creating bus_reports table...\n";
        $sql = file_get_contents(__DIR__ . '/../database/create_reports_table.sql');
        $pdo->exec($sql);
        echo "Table created successfully.\n";
    } else {
        echo "Table already exists.\n";
    }
    
    // Check if we have any reports
    $count = $pdo->query("SELECT COUNT(*) as count FROM bus_reports")->fetch()['count'];
    
    if ($count == 0) {
        echo "Adding sample report data...\n";
        $sampleData = [
            [
                'bus_number' => 'WP-CB-1234',
                'issue_types' => 'overcrowding,late',
                'description' => 'Bus was extremely overcrowded and arrived 30 minutes late.',
                'location' => 'Colombo Fort',
                'date_time' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'status' => 'pending',
                'reporter_name' => 'John Doe',
                'reporter_email' => 'john@example.com',
                'is_anonymous' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'bus_number' => 'WP-KA-5678',
                'issue_types' => 'rude_driver',
                'description' => 'The driver was very rude to passengers.',
                'location' => 'Kandy',
                'date_time' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'reviewed',
                'reporter_name' => 'Jane Smith',
                'reporter_email' => 'jane@example.com',
                'is_anonymous' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'bus_number' => 'WP-CB-1234',
                'issue_types' => 'mechanical_issue',
                'description' => 'Bus was making strange noises and had a flat tire.',
                'location' => 'Galle',
                'date_time' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'reporter_name' => 'Anonymous',
                'reporter_email' => '',
                'is_anonymous' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO bus_reports 
            (bus_number, issue_types, description, location, date_time, status, 
             reporter_name, reporter_email, is_anonymous, created_at, updated_at) 
            VALUES 
            (:bus_number, :issue_types, :description, :location, :date_time, :status, 
             :reporter_name, :reporter_email, :is_anonymous, :created_at, :updated_at)");
        
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "Added " . count($sampleData) . " sample reports.\n";
    } else {
        echo "Found $count existing reports.\n";
    }
    
    echo "\nYou can now view reports at: http://" . $_SERVER['HTTP_HOST'] . "/BS/admin/view-reports.php\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
