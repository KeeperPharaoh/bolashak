<?php

namespace App\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class TestCheckBoxFormField extends AbstractHandler
{
    protected $codename = 'test_checkbox_field';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('formfields.checkbox', [
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
