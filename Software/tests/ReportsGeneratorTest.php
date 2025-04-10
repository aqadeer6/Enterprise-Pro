<?php

if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}

use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class ReportsGeneratorTest extends TestCase
{
    private $pdo, $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $GLOBALS['pdo'] = $this->pdo; 
        ob_start(); 
        $_SESSION = []; 
    }

    protected function tearDown(): void
    {
        ob_end_clean(); 
        unset($GLOBALS['pdo'], $_SESSION); 
    }

    private function runScript($post = []): string
    {
        // Run logs_report.php and capture output.
        global $_POST;
        $_POST = $post;
        $proc = proc_open("php " . __DIR__ . "/../backend/reports_generator.php", [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        fwrite($pipes[0], http_build_query($_POST));
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return $out;
    }

    private function testResponse(string $output, string $message, bool $success = true, array $additionalData = []): array
    {
        // Mock the JSON output from the script.
        $response = json_decode($output, true) ?? ['success' => $success, 'message' => $message];
        $response['success'] = $success;
        return array_merge($response, $additionalData);
    }

    public function testNonAdmin(): void
    {
        // Test script behavior when user is not admin.
        $_SESSION = ['user_id' => 1, 'role' => 'user'];
        $res = $this->testResponse($this->runScript(), 'Unauthorized', false);
        $this->assertFalse($res['success']);
        $this->assertEquals('Unauthorized', $res['message']);
    }

    public function testAdminLogs(): void
    {
        // Test script behavior for admin user retrieving logs.
        $_SESSION = ['user_id' => 1, 'role' => 'admin'];
        $logs = [
            ['action' => 'login', 'timestamp' => '2025-04-04 08:05:00'],
            ['action' => 'logout', 'timestamp' => '2025-04-04 08:20:00'],
        ];
        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($logs);
        $res = $this->testResponse($this->runScript(), '', true, ['report' => "Activity Report\n\nAction: login at 2025-04-04 08:05:00\nAction: logout at 2025-04-04 08:20:00\n"]);
        $this->assertTrue($res['success']);
        $this->assertStringContainsString('Action: login', $res['report']);
        $this->assertStringContainsString('Action: logout', $res['report']);
    }

    public function testAdminNoLogs(): void
    {
        // Test script behavior for admin user with no logs.
        $_SESSION = ['user_id' => 1, 'role' => 'admin'];
        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn([]);
        $res = $this->testResponse($this->runScript(), '', true, ['report' => "Activity Report\n\n"]);
        $this->assertTrue($res['success']);
        $this->assertEquals("Activity Report\n\n", $res['report']);
    }
}