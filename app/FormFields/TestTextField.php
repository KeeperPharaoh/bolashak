<?php

namespace App\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class TestTextField extends AbstractHandler
{
    protected $codename = 'test_text';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('formfields.text', [
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
