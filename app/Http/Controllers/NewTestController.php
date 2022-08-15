<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\RegularCategoryTest;
use App\Models\RegularQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewTestController extends Controller
{
    public function startCreating(): JsonResponse
    {
        $category = RegularCategoryTest::query()
                                       ->create([
                                                    'title'       => '',
                                                    'language'    => '',
                                                    'instruction' => '',
                                                ])
        ;

        return response()->json([
                                    'id' => $category->id,
                                ]);
    }

    public function main(int $id): JsonResponse
    {
        $category = RegularCategoryTest::query()
                                       ->findOrFail($id)
        ;

        if (request()->has('title')) {
            $category->title = \request('title');
        }
        if (request()->has('language')) {
            $category->language = \request('language');
        }
        if (request()->has('instruction')) {
            $category->instruction = \request('instruction');
        }
        $category->save();

        return response()->json(
            $category
        );
    }

    public function questionCreate(int $id): JsonResponse
    {
        $question = RegularQuestion::query()
                                   ->create(
                                       [
                                           'test_id'  => $id,
                                           'question' => null,
                                           'type'     => '',
                                           'image'    => '',
                                       ]
                                   )
        ;

        return response()->json(
            [
                'id' => $question->id,
            ]
        );
    }

    public function questionUpdate(int $id, Request $request): JsonResponse
    {
        $data     = $request->all();
        $question = RegularQuestion::query()
                                   ->findOrFail($id)
        ;


        if (isset($data['question'])) {
            $question->question = $data['question'];
        }
        if (request()->has('type')) {
            $type           = $data['common'] ? 'common' : 'multiple_choice';
            $question->type = $type;
        }
        if (request()->has('image')) {
            $question->image = $this->imageUpload($data['image'], 'questions');
        }
        $question->save();
        if (isset($question['image'])) {
            $question['image'] = env('APP_URL') . Storage::url($question['image']);
        }

        return response()->json(
            $question
        );
    }

    public function questionDelete(int $id): JsonResponse
    {
        $question = RegularQuestion::query()
                                   ->findOrFail($id)
        ;
        Storage::delete('public/' . $question->image);
        $question->delete();

        return response()->json(
            [
                'message' => 'Question deleted',
            ]
        );
    }

    public function answerCreate(int $id): JsonResponse
    {
        $answer = Answer::query()
                        ->create([
                                     'question_id' => $id,
                                     'answer'      => '',
                                     'is_correct'  => false,
                                     'image'       => '',
                                 ])
        ;

        return response()->json(
            [
                'id' => $answer->id,
            ]
        );
    }

    public function answerUpdate(int $id, Request $request)
    {
        $data     = $request->all();
        $question = Answer::query()
                          ->findOrFail($id)
        ;


        if (isset($data['title'])) {
            $question->answer = $data['title'];
        }
        if (request()->has('right')) {
            $question->is_correct = $data['right'];
        }
        if (request()->has('image')) {
            $question->image = $this->imageUpload($data['image'], 'questions');
        }

        $question->save();
        if (isset($question['image'])) {
            $question['image'] = env('APP_URL') . Storage::url($question['image']);
        }

        return response()->json(
            $question
        );
    }

    public function answerDelete(int $id): JsonResponse
    {
        $question = Answer::query()
                                   ->findOrFail($id)
        ;
        Storage::delete('public/' . $question->image);
        $question->delete();

        return response()->json(
            [
                'message' => 'Question deleted',
            ]
        );
    }

    protected function imageUpload($image, $path)
    {
        try {
            preg_replace('/^data:image\/\w+;base64,/', '', $image);
            $type = explode(';', $image)[0];
            $type = explode('/', $type)[1]; // png or jpg etc

            if (empty($type)) {
                $pathR = env('APP_URL') . '/storage/';
                $image = str_replace($pathR, '', $image);

                return $image;
            }

            $image = str_replace('data:image/' . $type . ';base64,', '', $image);
            $image = str_replace(' ', '+', $image);


            $imageName = "p-" . time() . "." . $type;

            Storage::disk('public')
                   ->put($path . '/' . $imageName, base64_decode($image))
            ;


            return $path . '/' . $imageName;

        } catch (\Exception $e) {
            return null;
        }
    }
}
