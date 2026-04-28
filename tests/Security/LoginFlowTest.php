<?php

namespace App\Tests\Security;

use App\Entity\Society;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginFlowTest extends WebTestCase
{
    public function testAdminLoginRedirectsToAdminDashboard(): void
    {
        // Use the dev environment/database to match local XAMPP usage.
        $client = static::createClient(['environment' => 'dev']);

        $crawler = $client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $csrf = (string) $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_username' => 'admin@example.com',
            '_password' => 'admin1234',
            '_csrf_token' => $csrf,
        ]);

        self::assertResponseRedirects('/admin/dashboard');
    }

    public function testSocietyLoginRedirectsToSocietyDashboard(): void
    {
        // Use the dev environment/database to match local XAMPP usage.
        $client = static::createClient(['environment' => 'dev']);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $email = 'test-society@example.com';
        $plainPassword = 'test1234';

        // Use the entity manager repository (no container repository services assumptions).
        $existing = $em->getRepository(Society::class)->findOneBy(['email' => $email]);
        if ($existing instanceof Society) {
            $em->remove($existing);
            $em->flush();
        }

        $society = (new Society())
            ->setName('Test Society')
            ->setEmail($email)
            ->setPassword($hasher->hashPassword(new Society(), $plainPassword))
            ->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($society);
        $em->flush();

        $crawler = $client->request('GET', '/society/login');
        self::assertResponseIsSuccessful();

        $csrf = (string) $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/society/login', [
            '_username' => $email,
            '_password' => $plainPassword,
            '_csrf_token' => $csrf,
        ]);

        self::assertResponseRedirects('/society/dashboard');
    }
}
