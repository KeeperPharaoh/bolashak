<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\RegularCategoryTest;
use App\Models\RegularQuestion;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerTestController extends VoyagerBaseController
{
    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name.'.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

                $searchField = $dataType->name.'.'.$search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                                       $dataType->name.'.*',
                                       'joined.'.$row->details->label.' as '.$orderBy,
                                   ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                                                      $query->orderBy($orderBy, $querySortOrder),
                                                      $getter,
                                                  ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }
        $new = [];
        foreach ($dataTypeContent as $item) {
            if ($item->type == 'main_items') {
                $new[] = $item;
            }
        }
        $dataTypeContent = $new;
        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn'
        ));
    }

    public function destroy(Request $request, $id)
    {
        RegularCategoryTest::query()
                           ->where('id', $id)
                           ->delete()
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
        return redirect()->route('voyager.regular-category-tests.index');
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

        $category  = RegularCategoryTest::query()
                                        ->where('id', $id)
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
            $view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'category', 'questions')
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

        return redirect()->route('voyager.regular-category-tests.index');
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
                                                ])
        ;

        $data = $requestData['array'];

        foreach ($data as $item) {
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

        return redirect()->route('voyager.regular-category-tests.index');
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
