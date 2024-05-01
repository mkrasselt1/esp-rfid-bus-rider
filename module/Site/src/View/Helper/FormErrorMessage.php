<?php
namespace Site\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class FormErrorMessage extends AbstractHelper{
    
    public function __invoke(string $errorMessage){
        if(!empty($errorMessage)){
            echo "\t\t\t<div class=\"alert alert-warning\">$errorMessage</div>\n";
        }
    }
}