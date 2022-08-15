<?php

namespace App\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class TestTextFormField extends AbstractHandler
{
    protected $codename = 'test_form_field';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('formfields.test_text_form_field', [
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
