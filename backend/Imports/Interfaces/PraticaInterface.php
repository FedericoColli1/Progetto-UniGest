<?php

interface PraticaInterface {

    public static function getPraticheAmministrativo($IdAmministrtivo);

    public static function getPratiche($IdUser);

    public static function getPraticheDirettore($IdUnita);

    public static function addPratica($IdUser,$data = []);

    public static function DeletePratica($IdPratica);

    public static function GetMaxPassaggio($IdPratica);

    public static function getPraticheJolly();
}