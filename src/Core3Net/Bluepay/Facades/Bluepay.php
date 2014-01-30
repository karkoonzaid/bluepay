<?php
namespace Core3Net\Bluepay\Facades;
use Illuminate\Support\Facades\Facade;
class Bluepay extends Facade
{
    protected static function getFacadeAccessor() { return 'Bluepay'; }
}