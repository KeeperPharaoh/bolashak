<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\MainSubject;
use App\Models\RegularCategoryTest;
use App\Models\RegularQuestion;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerProfileTestController extends VoyagerBaseController
{

    public function destroy(Request $request, $id)
    {
        RegularCategoryTest::query()
                           ->where('id', $id)
                           ->delete()
        ;
        MainSubject::query()
            ->where('id', $id)
            ->delete()
        ;
        $questions     = RegularQuestion::query()
                                        ->where('test_id', $id)
                                        ->get()
        ;
        $questions_ids = $questions->pluck('id');

        Answer::query()
              ->whereIn('question_id', $questions_ids)
              ->delete()
        ;
        RegularQuestion::query()
                       ->where('test_id', $id)
                       ->delete()
        ;

        return redirect()->route('voyager.main-subjects.index');
    }

    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')
                           ->where('slug', '=', $slug)
                           ->first()
        ;

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists(
                    $model, 'scope' . ucfirst($dataType->scope)
                )) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)
                                 ->where('id', $id)
                                 ->first()
            ;
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted'));
    }

    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')
                           ->where('slug', '=', $slug)
                           ->first()
        ;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists(
                    $model, 'scope' . ucfirst($dataType->scope)
                )) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)
                                 ->where('id', $id)
                                 ->first()
            ;
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        $mainSubject = MainSubject::query()->where('id', $id)->first()->toArray();

        $category  = RegularCategoryTest::query()
                                        ->where('id', $mainSubject['test_id'])
                                        ->first()
        ;

        $questions = RegularQuestion::query()
                                    ->where('test_id', $category->id)
                                    ->get()
        ;
        foreach ($questions as $question) {
            $question->answers = Answer::query()
                                       ->where('question_id', $question->id)
                                       ->get()
            ;
        }

        return Voyager::view(
            $view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'category', 'questions', 'mainSubject')
        );
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();

        RegularCategoryTest::query()
                           ->where('id', $id)
                           ->update([
                                        'title'    => $data['title'],
                                        'language' => $data['language'],
                                    ])
        ;
        MainSubject::query()
                   ->where('id', $data['main'])
                   ->update([
                                'title'    => $data['title'],
                                'language' => $data['language'],
                            ])
        ;

        $questions = RegularQuestion::query()
                                    ->where('test_id', $id)
                                    ->get()
        ;

        $questions_ids = $questions->pluck('id');

        Answer::query()
              ->whereIn('question_id', $questions_ids)
              ->delete()
        ;
        RegularQuestion::query()
                       ->where('test_id', $id)
                       ->delete()
        ;

        $data = $data['array'];

        foreach ($data as $item) {

            if (count($item) > 7) {
                $question = RegularQuestion::query()
                                           ->create([
                                                        'test_id'  => $id,
                                                        'question' => $item['question'],
                                                        'type'     => 'multiple_choice',
                                                    ])
                ;
                $this->createAnswer($item[0], $question->id);
                $this->createAnswer($item[1], $question->id);
                $this->createAnswer($item[2], $question->id);
                $this->createAnswer($item[3], $question->id);
                $this->createAnswer($item[4], $question->id);
                $this->createAnswer($item[5], $question->id);
                $this->createAnswer($item[6], $question->id);
                $this->createAnswer($item[7], $question->id);
            } else {
                $question = RegularQuestion::query()
                                           ->create([
                                                        'test_id'  => $id,
                                                        'question' => $item['question'],
                                                    ])
                ;
                $this->createAnswer($item[0], $question->id);
                $this->createAnswer($item[1], $question->id);
                $this->createAnswer($item[2], $question->id);
                $this->createAnswer($item[3], $question->id);
                $this->createAnswer($item[4], $question->id);
            }
        }


        return redirect()->route('voyager.main-subjects.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $slug        = $this->getSlug($request);
        $requestData = $request->all();
        //$category = $requestData['category'];
        //Создание новой категории

        $category = RegularCategoryTest::query()
                                       ->create([
                                                    'title'    => $requestData['title'],
                                                    'language' => $requestData['language'],
                                                    'type'     => 'profile_items',
                                                ])
        ;
        MainSubject::query()
                           ->create([
                                        'title'    => $requestData['title'],
                                        'language' => $requestData['language'],
                                        'type'     => 'profile_items',
                                        'test_id'  => $category->id,
                                    ])
        ;
        $data = $requestData['array'];

        foreach ($data as $item) {

            if (count($item) > 7) {
                $question = RegularQuestion::query()
                                           ->create([
                                                        'test_id'  => $category->id,
                                                        'question' => $item['question'],
                                                        'type'     => 'multiple_choice',
                                                    ])
                ;
                $this->createAnswer($item[0], $question->id);
                $this->createAnswer($item[1], $question->id);
                $this->createAnswer($item[2], $question->id);
                $this->createAnswer($item[3], $question->id);
                $this->createAnswer($item[4], $question->id);
                $this->createAnswer($item[5], $question->id);
                $this->createAnswer($item[6], $question->id);
                $this->createAnswer($item[7], $question->id);
            } else {
                $question = RegularQuestion::query()
                                           ->create([
                                                        'test_id'  => $category->id,
                                                        'question' => $item['question'],
                                                    ])
                ;
                $this->createAnswer($item[0], $question->id);
                $this->createAnswer($item[1], $question->id);
                $this->createAnswer($item[2], $question->id);
                $this->createAnswer($item[3], $question->id);
                $this->createAnswer($item[4], $question->id);
            }
        }

        return redirect()->route('voyager.main-subjects.index');
    }

    public function createAnswer($question, $answer_id)
    {
        $correct = false;
        if (isset($question[1])) {
            $correct = true;
        }

        Answer::query()
              ->create([
                           'question_id' => $answer_id,
                           'answer'      => $question[0],
                           'is_correct'  => $correct,
                       ])
        ;
    }
}
