<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminDashboardTest extends WebTestCase
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

    public function testDashboardAccess(): void
    {
        // Create Admin
        $user = new \App\Entity\User();
        $user->setEmail('admin_dash@test.com');
        $user->setRoles(['ROLE_DEV']);
        $user->setName('Admin Dash');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password')
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/admin/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Dashboard');
        
        // Check sidebar links
        $this->assertAnySelectorTextContains('nav', 'Dashboard');
        $this->assertAnySelectorTextContains('nav', 'References');
        $this->assertAnySelectorTextContains('nav', 'Users');
    }
}
