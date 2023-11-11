<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ControllerTrait
{

    public Model $model;
    public string $viewPath;
    public string $routeName;



    public function __construct()
    {
        $controllerName = class_basename($this);
        $controllerName = str_replace('Controller', '', $controllerName);
        //Is has model
        if (file_exists(app_path('Models/' . $controllerName . '.php'))) {
            $this->model = app("App\Models\\" . $controllerName);
        }
        //Is has view
        if (!file_exists(resource_path('views/backend/' . strtolower($controllerName)))) {
            $this->viewPath = 'admin.pages.default';
        } else {
            $this->viewPath = 'admin.' . strtolower($controllerName);
        }
        $this->routeName = strtolower($controllerName);

    }
    /**
     * Crud Actions
     */
    public function index()
    {
        $data = $this->model->paginate(12);
        $fields = $this->model->getHtmlTableColumns();
        $model = $this->model;
        $route = 'admin.'.$this->routeName;

        return view($this->viewPath . '.index', compact('data','fields','model','route'));
    }

    public function create()
    {
        $fields = $this->model->getFieldsTypes();

        $model = $this->model;
        $route = route('admin.'.$this->routeName.'.store');
        $method = 'POST';
        $data = false;
        return view($this->viewPath . '.create',compact('fields','model','route','method','data'));
    }

    public function store(Request $request)
    {
        
        if (!$this->validateCheck($request,'Store'.$this->routeName.'Request')) {
            return redirect()->back()->withInput();
        }
        

        $this->model->fillData($request->toArray());

        return redirect()->route($this->routeName . '.index');
    }

    public function show($id)
    {
        $data = $this->model->find($id);
        return view($this->viewPath . '.show', compact('data'));
    }

    public function edit($id)
    {
        $fields = $this->model->getFieldsTypes();
        $model = $this->model;
        $route = route('admin.'.$this->routeName.'.update',$id);
        $method = 'PUT';
        $data = $this->model->find($id);
        return view($this->viewPath . '.edit', compact('data','fields','model','route','method'));
    }

    public function delete($id)
    {
        $this->model->find($id)->delete();
        return redirect()->route($this->routeName . '.index');
    }

    public function update(Request $request, $id)
    {
        $file_fields = $this->model->getFieldsStatic();
        $model = $this->model->find($id);
        if (count($file_fields) > 0) {
            foreach ($file_fields['file'] as $field) {
                if ($request->hasFile($field)) {
                    $model->deleteFile($field);
                    $model->uploadFile($request->file($field));
                }
            }
        }

        $model->fillData($request->toArray());
        return redirect()->route($this->routeName . '.index');
    }


    public function validateCheck($request,$name): bool
    {
        $func_request = app("App\Http\Requests\\" . $name);
        $rules = $func_request->rules();
        $messages = $func_request->messages();
        $startRequest = Request::capture();
        $validator = app('validator')->make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $startRequest->session()->flash('error', $validator->errors()->first());
            return false;
        }
        return true;
    }

}
