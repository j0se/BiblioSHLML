<?php

namespace SHLML\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testShowdocuments()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/docs');
    }

    public function testNewdocument()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/newDocument');
    }

    public function testShowusers()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/users');
    }

    public function testNewuser()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/newUser');
    }

}
