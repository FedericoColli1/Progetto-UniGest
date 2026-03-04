<?php

interface UserInterface {
    public function getId();

    public function getNome();

    public function getMail();

    public function getPwd();

    public function getDataCreazione();

    public function getJolly();

    public function getPratiche();

    public function getDocumenti($tipologia);

    public function addPratica($data = []);
}