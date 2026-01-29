<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->passwordHasher = $this->client->getContainer()
            ->get('security.user_password_hasher');
            
        // Clean up users
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }
    
    // Helper to create user
    private function createUser(string $email, string $role): void
    {
        $user = new \App\Entity\User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setName('Test ' . $role);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'password'
        );
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function testLoginRedirectToAdmin(): void
    {
        $this->createUser('admin@test.com', 'ROLE_DEV');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form();
        $form['_username'] = 'admin@test.com';
        $form['_password'] = 'password';
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/login/redirect');
        $this->client->followRedirect(); // to /login/redirect
        $this->assertResponseRedirects('/admin/');
    }

    public function testLoginRedirectToEditor(): void
    {
        $this->createUser('editor@test.com', 'ROLE_EDITOR');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form();
        $form['_username'] = 'editor@test.com';
        $form['_password'] = 'password';

        $this->client->submit($form);

        $this->assertResponseRedirects('/login/redirect');
        $this->client->followRedirect();
        $this->assertResponseRedirects('/dashboard/');
    }
    
    public function testAdminAccessDeniedForEditor(): void
    {
         $this->createUser('editor_denied@test.com', 'ROLE_EDITOR');
         
         // Log in as editor
         $this->client->loginUser($this->entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'editor_denied@test.com']));
         
         // Try to access admin dashboard
         $this->client->request('GET', '/admin');
         $this->assertResponseStatusCodeSame(403);
    }
}
