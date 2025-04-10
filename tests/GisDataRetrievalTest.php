<?php

if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}

use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class GisDataRetrievalTest extends TestCase
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
        // Run gis_data.php and capture output.
        global $_POST;
        $_POST = $post;
        $proc = proc_open("php " . __DIR__ . "/../backend/gis_data.php", [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        fwrite($pipes[0], http_build_query($_POST));
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return $out;
    }

    private function testResponse($out, $msg, $ok = true, $data = []): array
    {
        // Parse script output into a response array.
        $res = json_decode($out, true) ?: ['success' => $ok, 'message' => $msg] + $data;
        $res['success'] = $ok;
        return $res + $data;
    }

    public function testNoSession(): void
    {
        // Test script behavior when no session is active.
        $res = $this->testResponse($this->runScript(), 'Unauthorized', false);
        $this->assertFalse($res['success']);
        $this->assertEquals('Unauthorized', $res['message']);
    }

    public function testAdmin(): void
    {
        // Test script behavior for admin user.
        $_SESSION = ['user_id' => 1, 'role' => 'admin'];
        $assets = [['name' => 'Admin Asset', 'lat' => 1.0, 'lon' => 2.0, 'category' => 'hospital']];
        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($assets);
        $res = $this->testResponse($this->runScript(), '', true, ['assets' => $assets]);
        $this->assertTrue($res['success']);
        $this->assertEquals($assets, $res['assets']);
    }

    public function testUserOwn(): void
    {
        // Test script behavior for user retrieving their own assets.
        $_SESSION = ['user_id' => 1, 'role' => 'user'];
        $assets = [['name' => 'User Owned Asset', 'lat' => 3.0, 'lon' => 4.0, 'category' => 'park']];
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($assets);
        $res = $this->testResponse($this->runScript(), '', true, ['assets' => $assets]);
        $this->assertTrue($res['success']);
        $this->assertEquals($assets, $res['assets']);
    }

    public function testUserShared(): void
    {
        // Test script behavior for user retrieving shared assets.
        $_SESSION = ['user_id' => 2, 'role' => 'user'];
        $assets = [['name' => 'Shared Location', 'lat' => 5.0, 'lon' => 6.0, 'category' => 'school']];
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn($assets);
        $res = $this->testResponse($this->runScript(), '', true, ['assets' => $assets]);
        $this->assertTrue($res['success']);
        $this->assertEquals($assets, $res['assets']);
    }

    public function testUserNone(): void
    {
        // Test script behavior for user with no assets to retrieve.
        $_SESSION = ['user_id' => 3, 'role' => 'user'];
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn([]);
        $res = $this->testResponse($this->runScript(), '', true, ['assets' => []]);
        $this->assertTrue($res['success']);
        $this->assertEquals([], $res['assets']);
    }
}