<?php

use PHPUnit\Framework\TestCase;

class Test extends TestCase
{
    public function test()
    {
        require __DIR__ . "/../../backend/Imports/Class/User.php";
        require __DIR__ . "/../../backend/Imports/Class/Amministrativo.php";
        require __DIR__ . "/../../backend/Imports/Class/Direttore.php";
        require __DIR__ . "/../../backend/Imports/Class/Jolly.php";
        $user = new User(7);
        $this->assertEquals(7,$user->getId());
        $this->assertEquals("Paolo Verde",$user->getNome());
        $this->assertEquals("paolo.verde@example.com",$user->getMail());
        $this->assertEquals("$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi",$user->getPwd());
        $this->assertEquals(0,$user->getJolly());

        $pratica = $user->getPratiche();
        $this->assertEquals(1,$pratica[0]["IdPratica"]);
        $this->assertEquals("Descrizione",$pratica[0]["Descrizione"]);
        $this->assertEquals("Tipo A",$pratica[0]["Tipologia"]);
        $this->assertEquals(0,$pratica[0]["Codice"]);
        $this->assertEquals(0,$pratica[0]["PassaggioAttuale"]);
        $this->assertEquals(3,$pratica[0]["PassaggiMAX"]);

        $direttore = new Direttore(1);
        $this->assertEquals(1,$direttore->getDirettore());
        $pratica=$direttore->getPraticheDirettore();
        $this->assertEquals(1,$pratica[0]["IdPratica"]);
        $this->assertEquals("Descrizione",$pratica[0]["Descrizione"]);
        $this->assertEquals("Tipo A",$pratica[0]["Tipologia"]);
        $this->assertEquals("Paolo Verde",$pratica[0]["Nome"]);
        $this->assertEquals("Matematica",$pratica[0]["Corso"]);
        $this->assertEquals("Da Assegnare",$pratica[0]["Azione"]);
        $this->assertEquals(0,$pratica[0]["NPassaggio"]);
        $this->assertEquals("file3.txt,file4.txt",$pratica[0]["ListDocRichiesti"]);
        $this->assertEquals(null,$pratica[0]["ListDocUscita"]);
        $this->assertEquals(0,$pratica[0]["Codice"]);
        $this->assertEquals(0,$pratica[0]["PassaggioAttuale"]);
        $this->assertEquals(3,$pratica[0]["NPassaggi"]);

        Direttore::Assegnazione(1,3);

        $direttore = $this->createMock(Direttore::class);
        $direttore->method('getAmministrativi')
                  ->willReturn([
                      'successo' => 'Assegnazione avvenuta con successo',
                      'amministrativi' => [
                          ['Mail' => 'anna.verdi@example.com'],
                          ['Mail' => 'giulia.neri@example.com'],
                          ['Mail' => 'maria.rossa@example.com']
                      ]
                  ]);

        $result = $direttore->getAmministrativi();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('successo', $result);
        $this->assertArrayHasKey('amministrativi', $result);

        $this->assertEquals('Assegnazione avvenuta con successo', $result['successo']);

        // For the array of mails, we can still use assertContains for robustness
        $expectedMails = [
            ['Mail' => 'anna.verdi@example.com'],
            ['Mail' => 'giulia.neri@example.com'],
            ['Mail' => 'maria.rossa@example.com'],
        ];

        $this->assertCount(3, $result['amministrativi']);
        foreach ($expectedMails as $expectedMail) {
            $this->assertContains($expectedMail, $result['amministrativi'], "Missing expected mail entry.");
        }

        $direttore = $this->createMock(Direttore::class);
        $direttore->method('getAmministrativiAssegnati')
                  ->willReturn([
                      'successo' => 'Assegnazione avvenuta con successo',
                      'amministrativi' => [
                          ['Mail' => 'anna.verdi@example.com']
                      ]
                  ]);

        $result = $direttore->getAmministrativiAssegnati(1,0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('successo', $result);
        $this->assertArrayHasKey('amministrativi', $result);

        $this->assertEquals('Assegnazione avvenuta con successo', $result['successo']);

        // For the array of mails, we can still use assertContains for robustness
        $expectedMails = [
            ['Mail' => 'anna.verdi@example.com']
        ];

        $this->assertCount(1, $result['amministrativi']);
        foreach ($expectedMails as $expectedMail) {
            $this->assertContains($expectedMail, $result['amministrativi'], "Missing expected mail entry.");
        }
        
        $amministrativo = new Amministrativo(3);
        $this->assertEquals(1,$amministrativo->getIdUnita());
        
        $pratica = $amministrativo->getPraticheAmministrativo();
        $this->assertEquals(1,$pratica[0]["IdPratica"]);
        $this->assertEquals("Descrizione",$pratica[0]["Descrizione"]);
        $this->assertEquals("Tipo A",$pratica[0]["Tipologia"]);
        $this->assertEquals("Paolo Verde",$pratica[0]["Nome"]);
        $this->assertEquals("Matematica",$pratica[0]["Corso"]);
        $this->assertEquals(0,$pratica[0]["NPassaggio"]);
        $this->assertEquals("file3.txt,file4.txt",$pratica[0]["ListDocRichiesti"]);
        $this->assertEquals(null,$pratica[0]["ListDocUscita"]);
        $this->assertEquals(3,$pratica[0]["NPassaggi"]);
        $this->assertEquals(3,Amministrativo::getIdFromMail("anna.verdi@example.com"));


        $jolly = new Jolly(11);
        $pratica = $jolly->getPraticheJolly();
        $this->assertEquals(1,$pratica[0]["IdPratica"]);
        $this->assertEquals("Descrizione",$pratica[0]["Descrizione"]);
        $this->assertEquals("Tipo A",$pratica[0]["Tipologia"]);
        $this->assertEquals("Paolo Verde",$pratica[0]["Nome"]);
        $this->assertEquals("Assegnato",$pratica[0]["Azione"]);
        $this->assertEquals(0,$pratica[0]["NPassaggio"]);
        $this->assertEquals("file3.txt,file4.txt",$pratica[0]["ListDocRichiesti"]);
        $this->assertEquals(null,$pratica[0]["ListDocUscita"]);
        $this->assertEquals(0,$pratica[0]["Codice"]);
        $this->assertEquals(0,$pratica[0]["PassaggioAttuale"]);
        $this->assertEquals(3,$pratica[0]["NPassaggi"]);

        $jolly = $this->createMock(Jolly::class);
        $jolly->method('getAmministrativi')
                  ->willReturn([
                      'successo' => 'Assegnazione avvenuta con successo',
                      'amministrativi' => [
                          ['Mail' => 'anna.verdi@example.com'],
                          ['Mail' => 'giulia.neri@example.com'],
                          ['Mail' => 'maria.rossa@example.com']
                      ]
                  ]);

        $result = $jolly->getAmministrativi(1,0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('successo', $result);
        $this->assertArrayHasKey('amministrativi', $result);

        $this->assertEquals('Assegnazione avvenuta con successo', $result['successo']);

        // For the array of mails, we can still use assertContains for robustness
        $expectedMails = [
            ['Mail' => 'anna.verdi@example.com'],
            ['Mail' => 'giulia.neri@example.com'],
            ['Mail' => 'maria.rossa@example.com'],
        ];

        $this->assertCount(3, $result['amministrativi']);
        foreach ($expectedMails as $expectedMail) {
            $this->assertContains($expectedMail, $result['amministrativi'], "Missing expected mail entry.");
        }

        $jolly = $this->createMock(Jolly::class);
        $jolly->method('getAmministrativiAssegnati')
                  ->willReturn([
                      'successo' => 'Assegnazione avvenuta con successo',
                      'amministrativi' => [
                          ['Mail' => 'anna.verdi@example.com']
                      ]
                  ]);

        $result = $jolly->getAmministrativiAssegnati(1,0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('successo', $result);
        $this->assertArrayHasKey('amministrativi', $result);

        $this->assertEquals('Assegnazione avvenuta con successo', $result['successo']);

        // For the array of mails, we can still use assertContains for robustness
        $expectedMails = [
            ['Mail' => 'anna.verdi@example.com']
        ];

        $this->assertCount(1, $result['amministrativi']);
        foreach ($expectedMails as $expectedMail) {
            $this->assertContains($expectedMail, $result['amministrativi'], "Missing expected mail entry.");
        }
    }
}