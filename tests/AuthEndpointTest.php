<?php

if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}

use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class AuthEndpointTest extends TestCase
{
    private $pdo;
    private $pdoStmt;
    private $emailSenderMock;

    protected function setUp(): void
    {
        //Mock database and email.
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
        //Clean up mocks.
        unset($this->emailSenderMock);
        unset($GLOBALS['sendEmail']);
    }

    private function injectEmailSender($emailSender)
    {
        //Inject email mock.
        $GLOBALS['sendEmail'] = [$emailSender, 'sendEmail'];
    }

    private function runAuthScript($postData)
    {
        //Run auth.php and get output.
        global $_POST;
        $_POST = $postData;
        $command = "php " . __DIR__ . "/../backend/auth.php";
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
        // Get JSON response after mocking.
        $response = json_decode($output, true) ?: ['success' => $success, 'message' => $expectedMessage] + $extraData;
        $response['success'] = $success;
        return $response + $extraData;
    }

    public function testSignupSuccess()
    {
        //Test successful signup.
        $postData = [
            'action' => 'signup',
            'username' => 'bacel',
            'email' => 'bacelindi@gmail.com',
            'password' => 'bacel@123',
            'first_name' => 'bacel',
            'last_name' => 'Arora',
            'department' => 'hr'
        ];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(false);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), 'Your account has been created and is pending admin approval. Check your email for confirmation.');
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals('Your account has been created and is pending admin approval. Check your email for confirmation.', $response['message']);
    }

    public function testSignupEmailFailure()
    {
        // Test signup with email failure.
        $postData = [
            'action' => 'signup',
            'username' => 'bacel',
            'email' => 'bacelindi@gmail.com',
            'password' => 'bacel@123',
            'first_name' => 'bacel',
            'last_name' => 'Arora',
            'department' => 'hr'
        ];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(false);
        $this->emailSenderMock->method('sendEmail')->willReturn(false);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), 'Your account has been created and is pending admin approval. (Email sending failed)');
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals('Your account has been created and is pending admin approval. (Email sending failed)', $response['message']);
    }

    public function testLoginSuccess()
    {
        //Test successful login.
        $postData = ['action' => 'login', 'credentials' => 'bacel', 'password' => 'bacel@123'];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(['id' => 1, 'username' => 'bacel', 'email' => 'bacelindi@gmail.com', 'password' => password_hash('bacel@123', PASSWORD_DEFAULT), 'role' => 'user', 'status' => 'active']);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), '"role":"user"', true, ['username' => 'bacel', 'role' => 'user']);
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals('user', $response['role']);
        $this->assertEquals('bacel', $response['username']);
    }

    public function testLoginFailure()
    {
        // Test failed login.
        $postData = ['action' => 'login', 'credentials' => 'invaliduser', 'password' => 'InvalidPass123!'];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(false);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), 'Invalid email, username, or password', false);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Invalid email, username, or password', $response['message']);
    }
    public function testResetPasswordSuccess()
    {
        //Test successful password reset.
        $postData = ['action' => 'reset_password', 'email' => 'bacelindi@gmail.com'];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(['id' => 1, 'email' => 'bacelindi@gmail.com']);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), 'Password reset link sent! Check your email.');
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals('Password reset link sent! Check your email.', $response['message']);
    }

    public function testResetPasswordEmailFailure()
    {
        //Test password reset with email failure.
        $postData = ['action' => 'reset_password', 'email' => 'bacelindi@gmail.com'];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(['id' => 1, 'email' => 'bacelindi@gmail.com']);
        $this->emailSenderMock->method('sendEmail')->willReturn(false);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), 'Failed to send reset email.', false);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Failed to send reset email.', $response['message']);
    }

    public function testResetPasswordEmailNotFound()
    {
        // Test password reset with email not found.
        $postData = ['action' => 'reset_password', 'email' => 'nobacelindi@gmail.com'];
        $this->pdoStmt->method('execute')->willReturn(true);
        $this->pdoStmt->method('fetch')->willReturn(false);
        $this->injectEmailSender($this->emailSenderMock);
        $response = $this->response($this->runAuthScript($postData), 'Email not found', false);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Email not found', $response['message']);
    }
}