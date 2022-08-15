<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\RegularCategoryTest;
use App\Models\RegularQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AdminTestController extends Controller
{
    public function index(int $id)
    {
        $test = RegularCategoryTest::query()
                                   ->where('id', $id)
                                   ->select('id', 'title', 'language')
                                   ->first()
        ;

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
                             ->select('id', 'answer', 'is_correct', 'image')
                             ->get()
            ;
            foreach ($answers as $answer) {
                if ($answer->image != null) {
                    $answer->image = env('APP_URL') . '/storage/' . $answer->image;
                }
                $answer->right = (bool)$answer->is_correct;
                unset($answer->is_correct);
            }
            $question->answers = $answers;
        }
        $test->questions = $questions;

        return response()->json($test);
    }

    public function create(Request $request): JsonResponse
    {
        $data         = json_decode($request->getContent(), true);
        $test         = $data['test'];
        $instructions = $data['instruction'] ?? null;

        //        try {
        DB::beginTransaction();
        $category = RegularCategoryTest::query()
                                       ->create([
                                                    'title'       => $data['title'],
                                                    'language'    => $data['language'],
                                                    'instruction' => $instructions,
                                                ])
        ;

        foreach ($test as $item) {
            $type = $item['common'] ? 'common' : 'multiple_choice';
            if (isset($item['image']) and !empty($item['image'])) {
                $image = $this->imageBase64Upload($item['image'], 'question');
            } else {
                $image = null;
            }
            $question = RegularQuestion::query()
                                       ->create([
                                                    'test_id'  => $category->id,
                                                    'question' => $item['question'] ?? null,
                                                    'type'     => $type,
                                                    'image'    => $image,
                                                ])
            ;
            foreach ($item['answers'] as $answer) {
                if (isset($answer['image']) and !empty($answer['image'])) {
                    $image = $this->imageBase64Upload($answer['image'], 'answer');
                } else {
                    $image = null;
                }
                Answer::query()
                      ->create([
                                   'question_id' => $question->id,
                                   'answer'      => $answer['title'],
                                   'is_correct'  => $answer['right'],
                                   'image'       => $image,
                               ])
                ;
            }
        }
        DB::commit();

        return response()->json([
                                    'status'  => true,
                                    'message' => 'Тест успешно создан',
                                ],
                                Response::HTTP_OK
        );
//        } catch (\Exception $exception) {
//            DB::rollBack();
//
//            return response()->json([
//                                        'status'  => false,
//                                        'message' => 'Что то не так',
//                                    ],
//                                    Response::HTTP_BAD_REQUEST
//            );
//        }
    }

    public function update($id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $test = $data['test'] ?? null;
//        try {
        DB::beginTransaction();
        RegularCategoryTest::query()
                           ->where('id', $id)
                           ->update([
                                        'title'    => $data['title'],
                                        'language' => $data['language'],
                                    ])
        ;
        if ($test != null) {
            foreach ($test as $item) {
                $type = $item['common'] ? 'common' : 'multiple_choice';

                if (isset($item['id'])) {
                    if ($item['image'] != null) {
                        $check = preg_replace('/^data:image\/\w+;base64,/', '', $item['image']);
                        if (base64_encode(base64_decode($check, true)) === $check) {
                            $image = $this->imageBase64Upload($item['image'], 'question');
                        } else {
                            $image = RegularQuestion
                                ::query()
                                ->where('id', $item['id'])
                                ->first()
                                ->image;
                        }
                    } else {
                        $image = null;
                    }
                    RegularQuestion::query()
                                   ->where('id', $item['id'])
                                   ->update([
                                                'test_id'  => $id,
                                                'question' => $item['question'],
                                                'type'     => $type,
                                                'image'    => $image,
                                            ])
                    ;
                    if (isset($item['answers'])) {
                        foreach ($item['answers'] as $answer) {
                            if (isset($answer['image'])) {
                                if ($answer['image'] != null) {
                                    $check = preg_replace('/^data:image\/\w+;base64,/', '', $answer['image']);
                                    if (base64_encode(base64_decode($check, true)) === $check) {
                                        $image = $this->imageBase64Upload($answer['image'], 'answer');
                                    } else {
                                        $image = Answer
                                            ::query()
                                            ->where('id', $answer['id'])
                                            ->first()
                                            ->image;
                                    }
                                } else {
                                    $image = null;
                                }
                            } else {
                                $image = null;
                            }
                            Answer::query()
                                  ->where('id', $answer['id'])
                                  ->update([
                                               'answer'     => $answer['title'],
                                               'is_correct' => $answer['right'],
                                               'image'      => $image,
                                           ])
                            ;
                        }
                    }
                } else {
                    if (isset($item['image'])) {
                        $image = $this->imageBase64Upload($item['image'], 'question');
                    } else {
                        $image = null;
                    }

                    $question = RegularQuestion::query()
                                               ->create([
                                                            'test_id'  => $id,
                                                            'question' => $item['question'],
                                                            'type'     => $type,
                                                            'image'    => $image,
                                                        ])
                    ;
                    if (isset($item['answers'])) {
                        if (isset($answer['image'])) {
                            $image = $this->imageBase64Upload($answer['image'], 'answer');
                        } else {
                            $image = null;
                        }
                        foreach ($item['answers'] as $answer) {
                            Answer::query()
                                  ->create([
                                               'question_id' => $question->id,
                                               'answer'      => $answer['title'],
                                               'is_correct'  => $answer['right'],
                                               'image'       => $image,
                                           ])
                            ;
                        }
                    }
                }
            }
        }
        DB::commit();

        return response()->json([
                                    'status'  => true,
                                    'message' => 'Тест успешно обновлен',
                                ],
                                Response::HTTP_OK
        );
//        } catch (\Exception $exception) {
//            DB::rollBack();
//
//            return response()->json([
//                                        'status'  => false,
//                                        'message' => 'Что то не так',
//                                    ],
//                                    Response::HTTP_BAD_REQUEST
//            );
//        }
    }

    public function delete(Request $request)
    {
        $data     = json_decode($request->getContent(), true);
        $question = RegularQuestion::query()
                                   ->where([
                                               'test_id' => $data['test_id'],
                                               'id'      => $data['questionId'],
                                           ])
                                   ->first()
        ;

        try {
            DB::beginTransaction();
            if (isset($data['answerId'])) {
                Answer::query()
                      ->where(['question_id' => $question->id, 'id' => $data['answerId']])
                      ->delete()
                ;
            } else {
                Answer::query()
                      ->where('question_id', $question->id)
                      ->delete()
                ;
                $question->delete();
            }
            DB::commit();

            return response()->json([
                                        'status'  => true,
                                        'message' => 'Успешно удален',
                                    ],
                                    Response::HTTP_OK
            );

        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                                        'status'  => false,
                                        'message' => 'Что то не так',
                                    ],
                                    Response::HTTP_BAD_REQUEST
            );
        }
    }

    protected function imageBase64Upload($image, $path): string
    {
        $img  = preg_replace('/^data:image\/\w+;base64,/', '', $image);
        $type = explode(';', $image)[0];
        $type = explode('/', $type)[1];

        $image = str_replace('data:image/' . $type . ';base64,', '', $image);
        $image = str_replace(' ', '+', $image);


        $imageName = "p-" . time() . "." . $type;

        Storage::disk('public')
               ->put($path . '/' . $imageName, base64_decode($image))
        ;


        return $path . '/' . $imageName;
    }

//    protected function imageBase64Upload(
//        $image, $path
//    ): string {
//        $img  = preg_replace('/^data:image\/\w+;base64,/', '', $image);
//        ;
//        $type = explode(';', $image);
//        $type = explode('/', $type[0])[1]; // png or jpg etc
//
//
//        $image = str_replace('data:image/' . $type . ';base64,', '', $image);
//        $image = str_replace(' ', '+', $image);
//
//
//        $imageName = "p-" . time() . "." . $type;
//
//        Storage::disk('public')
//               ->put($path . '/' . $imageName, base64_decode($image))
//        ;
//
//
//        return $path . '/' . $imageName;
//    }
}
