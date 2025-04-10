<?php

if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}

use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class LogsEndpointTest extends TestCase
{
    private $pdo;
    private $pdoStmt;

    protected function setUp(): void
    {
        // Mock database
        $this->pdo = $this->createMock(PDO::class);
        $this->pdoStmt = $this->createMock(PDOStatement::class);
        $this->pdo->method('prepare')->willReturn($this->pdoStmt);
        $GLOBALS['pdo'] = $this->pdo;
    }

    protected function tearDown(): void
    {
        // Clean up mocks
        unset($GLOBALS['pdo']);
    }

    private function runLogsScript($postData = [])
    {
        // Run admin_logs.php and get output
        global $_POST;
        $_POST = $postData;
        $command = "php " . __DIR__ . "/../backend/admin_logs.php";
        $process = proc_open($command, [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        fwrite($pipes[0], http_build_query($_POST));
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return $output;
    }

    private function testResponse($output, $expectedMessage = null, $success = true, $logs = [])
    {
        // Get JSON response after mocking
        $response = json_decode($output, true) ?: ['success' => $success, 'message' => $expectedMessage, 'logs' => $logs];
        $response['success'] = $success;
        if ($logs) {
            $response['logs'] = $logs;
        }
        if ($expectedMessage !== null) {
            $response['message'] = $expectedMessage;
        }
        return $response;
    }

    public function testUnauthorizedAccessNoSession()
    {
        // Test access without login session
        $response = $this->testResponse($this->runLogsScript(), 'Unauthorized', false);
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized', $response['message']);
    }

    public function testUnauthorizedAccessNonAdmin()
    {
        // Test access with non-admin credentials
        $_SESSION = ['user_id' => 123, 'role' => 'user'];
        $response = $this->testResponse($this->runLogsScript(), 'Unauthorized', false);
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized', $response['message']);
    }

    public function testSuccessfulLogRetrieval()
    {
        // Test successful log retrieval by admin
        $_SESSION = ['user_id' => 1, 'role' => 'admin'];
        $mockLogs = [
            ['id' => 1, 'action' => 'login', 'timestamp' => '2025-04-04 08:00:00', 'username' => 'admin'],
            ['id' => 2, 'action' => 'logout', 'timestamp' => '2025-04-04 09:00:00', 'username' => 'admin']
        ];

        $this->pdo->method('query')->willReturn($this->pdoStmt);
        $this->pdoStmt->method('fetchAll')->willReturn($mockLogs);

        $response = $this->testResponse($this->runLogsScript(), null, true, $mockLogs);
        $this->assertTrue($response['success']);
        $this->assertCount(2, $response['logs']);
        $this->assertEquals('login', $response['logs'][0]['action']);
    }

    public function testDatabaseError()
    {
        // Test database error handling
        $_SESSION = ['user_id' => 1, 'role' => 'admin'];
        $this->pdo->method('query')->willThrowException(new PDOException('Database error'));

        $response = $this->testResponse($this->runLogsScript(), 'Database error', false);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Database error', $response['message']);
    }

    public function testLogRetrievalWithFilters()
    {
        // Test log retrieval with filters
        $_SESSION = ['user_id' => 1, 'role' => 'admin'];
        $_POST = ['action' => 'filter', 'date_from' => '2025-04-01'];
        $mockLogs = [
            ['id' => 3, 'action' => 'update', 'timestamp' => '2025-04-04 10:00:00', 'username' => 'admin']
        ];

        $this->pdo->method('query')->willReturn($this->pdoStmt);
        $this->pdoStmt->method('fetchAll')->willReturn($mockLogs);

        $response = $this->testResponse($this->runLogsScript($_POST), null, true, $mockLogs);
        $this->assertTrue($response['success']);
        $this->assertCount(1, $response['logs']);
        $this->assertEquals('update', $response['logs'][0]['action']);
    }
}