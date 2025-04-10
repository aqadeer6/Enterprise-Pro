<?php

if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}

use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class CSVUploadTest extends TestCase
{
    private $pdo;
    private $pdoStmt;
    private $filesBackup;
    private $emailMock;

    protected function setUp(): void
    {
        // Initialize test environment and mocks
        $this->pdo = $this->createMock(PDO::class);
        $this->pdoStmt = $this->createMock(PDOStatement::class);
        $this->pdo->method('prepare')->willReturn($this->pdoStmt);
        $GLOBALS['pdo'] = $this->pdo;

        $this->emailMock = $this->getMockBuilder(stdClass::class)->addMethods(['sendEmail'])->getMock();
        $this->emailMock->method('sendEmail')->willReturn(true);

        $this->filesBackup = $_FILES;
        $_SESSION = ['user_id' => 1];
    }

    protected function tearDown(): void
    {
        // Clean up test environment
        unset($this->emailMock, $GLOBALS['sendEmail'], $GLOBALS['pdo'], $_SESSION);
        $_FILES = $this->filesBackup;
    }

    private function runScript($post, $files = []): string
    {
        // Execute the asset upload script with provided data
        global $_POST, $_FILES;
        $_POST = $post;
        $_FILES = $files;
        $cmd = "php " . __DIR__ . "/../backend/asset_upload.php";
        $proc = proc_open($cmd, [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        fwrite($pipes[0], http_build_query($_POST));
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return $out;
    }

    private function testResponse($output, $msg, $success = true, $extra = []): array
    {
        // Format and return standardized test response
        $resp = json_decode($output, true) ?: ['success' => $success, 'message' => $msg];
        $resp['success'] = $success;
        return $resp + $extra;
    }

    public function testNoSession(): void
    {
        // Verify access without valid session is denied
        unset($_SESSION['user_id']);
        $resp = $this->testResponse($this->runScript([]), 'Unauthorized', false);
        $this->assertFalse($resp['success']);
        $this->assertEquals('Unauthorized', $resp['message']);
    }

    public function testCsvSuccess(): void
    {
        // Test valid CSV file upload
        $csv = "name,latitude,longitude\nBradford Hospital,53.7948,-1.5467";
        $tmp = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmp, $csv);

        $_FILES = ['csv_file' => ['name' => 'hospital.csv', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK]];

        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(false);
        $GLOBALS['sendEmail'] = [$this->emailMock, 'sendEmail'];

        $resp = $this->testResponse($this->runScript(['action' => 'upload_csv']), 'Successfully uploaded 1 assets', true);
        $this->assertTrue($resp['success']);
        unlink($tmp);
    }

    public function testCsvInvalid(): void
    {
        // Test upload with malformed CSV file
        $csv = "name\nInvalid";
        $tmp = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmp, $csv);

        $_FILES = ['csv_file' => ['name' => 'hospital.csv', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK]];

        $resp = $this->testResponse($this->runScript(['action' => 'upload_csv']), 'Invalid CSV format: Expected at least 3 columns (name, latitude, longitude)', false);
        $this->assertFalse($resp['success']);
        unlink($tmp);
    }

    public function testSingleSuccess(): void
    {
        // Test successful individual asset upload
        $this->pdoStmt->method('execute')->willReturn(true);
        $GLOBALS['sendEmail'] = [$this->emailMock, 'sendEmail'];

        $resp = $this->testResponse($this->runScript(['action' => 'upload_individual', 'name' => 'Test', 'latitude' => 1.0, 'longitude' => 2.0]), 'Individual asset uploaded successfully', true);
        $this->assertTrue($resp['success']);
    }

    public function testDbError(): void
    {
        // Test database error during upload
        $this->pdoStmt->method('execute')->willThrowException(new PDOException('DB error'));
        $GLOBALS['sendEmail'] = [$this->emailMock, 'sendEmail'];

        $resp = $this->testResponse($this->runScript(['action' => 'upload_individual', 'name' => 'Test', 'latitude' => 1.0, 'longitude' => 2.0]), 'Database error: DB error', false);
        $this->assertFalse($resp['success']);
    }

    public function testInvalidAction(): void
    {
        // Test invalid action parameter
        $resp = $this->testResponse($this->runScript(['action' => 'invalid']), 'Invalid action', false);
        $this->assertFalse($resp['success']);
    }
}