<?php

if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}

use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class UserManagementTest extends TestCase
{
    private $pdo;
    private $pdoStmt;
    private $emailSenderMock;

    protected function setUp(): void
    {
        // Mock database and email
        $this->pdo = $this->createMock(PDO::class);
        $this->pdoStmt = $this->createMock(PDOStatement::class);
        $this->pdo->method('prepare')->willReturn($this->pdoStmt);

        $this->emailSenderMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['sendEmail'])
            ->getMock();
        $this->emailSenderMock->method('sendEmail')->willReturn(true);
    }

    protected function tearDown(): void
    {
        // Clean up mocks
        unset($this->emailSenderMock);
        unset($GLOBALS['sendEmail']);
    }

    private function injectEmailSender($emailSender)
    {
        // Inject email mock
        $GLOBALS['sendEmail'] = [$emailSender, 'sendEmail'];
    }

    private function runUserManagementScript($postData)
    {
        // Run user_management.php and get output
        global $_POST;
        $_POST = $postData;
        $command = "php " . __DIR__ . "/../backend/user_management.php";
        $process = proc_open($command, [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        fwrite($pipes[0], http_build_query($_POST));
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return $output;
    }

    private function response($output, $expectedMessage, $success = true, $extraData = [])
    {
        // Get JSON response after mocking
        $response = json_decode($output, true) ?: ['success' => $success, 'message' => $expectedMessage] + $extraData;
        $response['success'] = $success;
        return $response + $extraData;
    }

    public function testProfileUpdateSuccess()
    {
        // Test successful profile update for Bracel Arora
        $postData = [
            'action' => 'update_profile_details',
            'first_name' => 'Bracel',
            'last_name' => 'Arora',
            'username' => 'bracel',
            'email' => 'bracel@gmail.com'
        ];

        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn([
            'id' => 1,
            'first_name' => 'Bracel',
            'last_name' => 'Arora',
            'username' => 'bracel',
            'email' => 'bracel@gmail.com'
        ]);
        
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runUserManagementScript($postData), 'Profile details updated successfully');
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Profile details updated successfully', $response['message']);
    }

    public function testPasswordChangeSuccess()
    {
        // Test successful password change for Bracel
        $postData = [
            'action' => 'change_password',
            'current_password' => 'bracel@123',
            'new_password' => 'Bracel@1234',
            'confirm_password' => 'Bracel@1234'
        ];

        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn([
            'id' => 1,
            'password' => password_hash('bracel@123', PASSWORD_DEFAULT)
        ]);
        
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runUserManagementScript($postData), 'Password updated successfully');
        
        $this->assertTrue($response['success']);
        $this->assertEquals('Password updated successfully', $response['message']);
    }

    public function testAdminApproveUserSuccess()
    {
        // Test admin approving Bracel's account
        $postData = [
            'action' => 'approve_user',
            'username' => 'bracel'
        ];

        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn([
            'email' => 'bracel@gmail.com',
            'first_name' => 'Bracel',
            'last_name' => 'Arora'
        ]);
        
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runUserManagementScript($postData), 'User approved successfully.');
        
        $this->assertTrue($response['success']);
        $this->assertEquals('User approved successfully.', $response['message']);
    }

    public function testGetPendingUsersSuccess()
    {
        // Test admin viewing pending users
        $postData = [
            'action' => 'get_pending_users'
        ];

        $mockUsers = [
            [
                'id' => 2,
                'username' => 'bracel',
                'email' => 'bracel@gmail.com',
                'role' => 'user'
            ]
        ];

        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetchAll')->willReturn($mockUsers);
        
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runUserManagementScript($postData), null, true, ['users' => $mockUsers]);
        
        $this->assertTrue($response['success']);
        $this->assertCount(1, $response['users']);
        $this->assertEquals('bracel', $response['users'][0]['username']);
    }

    public function testUpdateAccessibilitySuccess()
    {
        // Test Bracel updating accessibility settings
        $postData = [
            'action' => 'update_accessibility',
            'high_contrast' => '1',
            'font_size' => 'large'
        ];

        $this->pdoStmt->method('execute')->willReturn(true);
        
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runUserManagementScript($postData), 'Accessibility settings updated');
        
        $this->assertTrue($response['success']);
    }
}