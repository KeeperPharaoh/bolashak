<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Answer;
use App\Models\Client;
use App\Models\DescriptionTest;
use App\Models\RegularCategoryTest;
use App\Models\RegularQuestion;
use App\Models\ResultTest;
use App\Models\TestClient;
use App\Models\TestInstruction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function user(UserRequest $request)
    {
        $data   = $request->validated();
        $client = Client::query()
                        ->create($data)
        ;

        return response()->json([
                                    'user_id' => $client->id,
                                ], 200);
    }

    public function categories()
    {
        $language = request()->input('language');

        if (!$language) {
            $language = 'ru';
        }

        $regularCategoryTests = RegularCategoryTest::query()
                                                   ->where('language', $language)
                                                   ->select('id', 'title')
                                                   ->get()
        ;


        return response()->json(
            $regularCategoryTests,
        );
    }

    public function description()
    {
        $language = request()->header('Accept-Language');

        if (!$language) {
            $language = 'ru';
        }
        if ($language == 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7') {
            $language = 'ru';
        }

        return DescriptionTest::query()
                              ->select('title', 'description')
                              ->first()
                              ->translate($language)
        ;
    }

    public function instruction()
    {
        $language = request()->header('Accept-Language');

        if (!$language) {
            $language = 'ru';
        }
        if ($language == 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7') {
            $language = 'ru';
        }

        return TestInstruction::query()
                              ->select('instruction')
                              ->first()
                              ->translate($language)
        ;
    }

    public function tests(Request $request): JsonResponse
    {
        $quizzes = $request->quizzes;

        $quizzes = explode(',', $quizzes);

        $tests = RegularCategoryTest::query()
                                    ->whereIn('id', $quizzes)
                                    ->select('id', 'title', 'language')
                                    ->get()
        ;

        foreach ($tests as $test) {
            $questions = RegularQuestion::query()
                                        ->where('test_id', $test->id)
                                        ->select('id', 'question', 'type', 'image')
                                        ->get()
            ;

            foreach ($questions as $question) {
                if ($question->image != null) {
                    $question->image = env('APP_URL') . '/storage/' . $question->image;
                }
                $answers = Answer::query()
                                 ->where('question_id', $question->id)
                                 ->select('id', 'answer', 'image')
                                 ->get()
                ;

                foreach ($answers as $answer) {
                    if ($answer->image != null) {
                        $answer->image = env('APP_URL') . '/storage/' . $answer->image;
                    }
                }

                $question->answers = $answers;
            }
            $test->questions = $questions;
        }

        return response()->json($tests);
    }

    public function results(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $examination = [];
        $result      = [];
        $tests       = $data['tests'];
        $total_right = 0;


        foreach ($tests as $test) {

            $catId = TestClient::query()
                               ->create([
                                            'user_id' => $data['user_id'],
                                            'test_id' => 1,
                                        ])
            ;
            $catId = $catId->id;

            $answers = $test['answers'];

            foreach ($answers as $answer) {
                $question = RegularQuestion::query()
                                           ->select('id', 'question', 'type')
                                           ->find($answer['question'])
                ;
                if ($question->type == "common") {
                    $your_answer = Answer::query()
                                         ->where('id', $answer['answer'])
                                         ->select('id', 'answer', 'is_correct')
                                         ->first()
                    ;
                    if ($your_answer != null) {
                        if ($your_answer->is_correct) {
                            $total_right++;
                            ResultTest::query()
                                      ->create([
                                                   'result_id'      => $catId,
                                                   'question'       => $question->id,
                                                   'points_awarded' => 1,
                                               ])
                            ;
                            $examination[] = [
                                'question'       => $question->id,
                                'points_awarded' => 1,
                            ];
                        } else {
                            ResultTest::query()
                                      ->create([
                                                   'result_id'      => $catId,
                                                   'question'       => $question->id,
                                                   'points_awarded' => 0,
                                               ])
                            ;
                            $examination[] = [
                                'question'       => $question->id,
                                'points_awarded' => 0,
                            ];
                        }

                    } else {
                        ResultTest::query()
                                  ->create([
                                               'result_id'      => $catId,
                                               'question'       => $question->id,
                                               'points_awarded' => 0,
                                           ])
                        ;
                        $examination[] = [
                            'question'       => $question->id,
                            'points_awarded' => 0,
                        ];
                    }
                } else {

                    $countCorrect = Answer::query()
                                          ->where('question_id', $question->id)
                                          ->where('is_correct', 1)
                                          ->count()
                    ;
                    $your_answers = Answer::query()
                                          ->whereIn('id', $answer['answer'])
                                          ->select('id', 'answer', 'is_correct')
                                          ->get()
                    ;

                    $sum = 2;

                    foreach ($your_answers as $your_answer) {
                        if (!$your_answer->is_correct) {
                            $sum -= 1;
                        }
                    }

                    if (count($your_answers) != $countCorrect) {
                        $abs = $countCorrect - count($your_answers);
                        $sum = $sum - $abs;
                    }
                    if ($sum < 0) {
                        $sum = 0;
                    }

                    ResultTest::query()
                              ->create([
                                           'result_id'      => $catId,
                                           'question'       => $question->id,
                                           'points_awarded' => $sum,
                                       ])
                    ;
                    $examination[] = [
                        'question'       => $question->id,
                        'points_awarded' => $sum,
                    ];
                    $total_right   += $sum;
                }
            }
            TestClient::query()
                      ->where('id', $catId)
                      ->update([
                                   'user_id' => $data['user_id'],
                                   'test_id' => $test['test_id'],
                                   'result'  => $total_right,
                               ])
            ;

            $category = RegularCategoryTest::query()
                                           ->where('id', $test['test_id'])
                                           ->first()
            ;

            $result[]    = [
                'result_id'   => $catId,
                'title'       => $category->title,
                'test_id'     => $test['test_id'],
                'result'      => $total_right,
                'examination' => $examination,
            ];
            $total_right = 0;
            $examination = [];
        }


        return response()->json([
                                    'data' => $result,
                                ]);
    }

    public function userResults(Request $request): JsonResponse
    {
        $ids = $request->ids;

        $ids = explode(',', $ids);

        $categories = TestClient::query()
                                ->whereIn('id', $ids)
                                ->select('id', 'user_id', 'result', 'test_id')
                                ->get()
        ;

        foreach ($categories as $category) {
            $category->title  = RegularCategoryTest::query()
                                                   ->where('id', $category->test_id)
                                                   ->first()
                ->title;
            $examination      = ResultTest::query()
                                          ->where('result_id', $category->id)
                                          ->select('question', 'points_awarded')
                                          ->get()
            ;
            $category->result = (int)$category->result;
            foreach ($examination as $item) {
                $item->question       = (int)$item->question;
                $item->points_awarded = (int)$item->points_awarded;
            }
            $category->examination = $examination;
        }

        return response()->json([
                                    'data' => $categories,
                                ]);
    }
}
