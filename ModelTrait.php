<?php

namespace App\Traits;

trait ModelTrait
{
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function getFieldsTypes()
    {
        $fillable = $this->fillable;
        $fields = [];
        if (count($fillable) == 0) {
            $fillable = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
        }
        $allFieldsTypes = $this->allFieldsTypes();
        foreach ($fillable as $field) {
            $type = [
                'type' => 'text',
                'required' => true,
                'label' => __('general.' . $field),
                'placeholder' => __('general.' . $field),
                'value' => $this->attributes[$field] ?? '',
                'options' => [],
                'name' => __('general.' . $field),
                'id' => $field . '-id-' . rand(1000, 9999),
                'key' => $field,
            ];
            foreach ($allFieldsTypes as $number){
                if (in_array($field, $number)){
                    $type['type'] = array_search($number, $allFieldsTypes);
                }else{
                    if (array_key_exists($field,$number)){
                        $type['type'] = array_search($number, $allFieldsTypes);
                        if ($type['type'] == 'select'){
                            switch ($field){
                                case 'category':
                                    if ($this->id != null){
                                        $type['options'] = $this->getCategories()->pluck('title', 'id')->toArray();
                                    }else{
                                        // Add your global category model ...
                                    }
                                    break;
                                case 'category_id':
                                    if ($this->id != null){
                                        $type['options'] = $this->getCategories()->pluck('title', 'id')->toArray();
                                    }else{
                                        // Add your global category model ...
                                    }
                                    break;
                                case 'parent_id':
                                    $type['options'] = $this->where('parent_id', 0)->where('parent_id', '!=', $this->id)->pluck('title', 'id')->toArray();
                                    break;
                                case 'status':
                                    $type['options'] = [
                                        1 => __('general.active'),
                                        0 => __('general.inactive')
                                    ];
                                    break;
                                default:
                                    $type['options'] = $allFieldsTypes[$type['type']][$field]['options'] ?? [];
                                    break;
                            }
                        }
                    }

                }
            }
            $fields[$field] = $type;
        }
        return $fields;
    }



    public function getHtmlTableColumns()
    {
        $columns = $this->getFieldsTypes();
        $session = session()->get('columns') ?? [
            'id',
            'title',
            'created_at',
            'media',
            'status',
            'is_deleted',
            'lang_key'
        ];
        $new_columns = [];
        foreach ($columns as $key => $column) {
            if (in_array($key, $session)) {
                $new_columns[$key] = $column;
            }
        }
        return $new_columns;

    }

    public static function fillData($request)
    {
        $model = (new static);
        $fillable = $model->fillable;
        if (count($fillable) == 0) {
            $fillable = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
        }
        if (array_key_exists('id', $request) && $model->find($request['id'])) {
            $model = $model->find($request['id']);
        }
        foreach ($fillable as $field) {
            if ($field != 'id' && $field != 'created_at' && $field != 'updated_at') {
                if ($field == 'password') {
                    $model->{$field} = bcrypt($request[$field] ?? '');
                } else {
                    if (strpos($field, '_id') !== false || strpos($field, 'is_') !== false || strpos($field, 'has_') !== false || strpos($field, 'status') !== false || strpos($field, 'sort') !== false || strpos($field, 'order') !== false || strpos($field, 'parent_id') !== false || strpos($field, 'quantity') !== false || strpos($field, 'price') !== false || strpos($field, 'order') !== false) {
                        $model->{$field} = intval($request[$field] ?? 0) ?? 0;
                    } else {
                        if (!in_array($field,$model->getFieldsStatic()['file'])){
                            $model->{$field} = $request[$field] ?? '';
                        }
                    }
                }
            }
        }
        $model->save();
        return $model;
    }

    private function allFieldsTypes()
    {
        return [
            'number' => [
                'price',
                'quantity',
                'order',
                'id'
            ],
            'text' => [
                'title',
                'name',
                'slug',
            ],
            'file' => [
                'image',
                'media'
            ],
            'checkbox' => [
            ],
            'textarea' => [
                'description',
                'content'
            ],
            'select' => [
                'category_id' => [
                    'options' => []
                ],
                'category' => [
                    'options' => []
                ],
                'parent_id' => [
                    'options' => []
                ],
                'status' => [
                    'options' => [],
                ],
                'is_deleted'=>[
                    'options'=>[
                        0 => __('general.inactive'),
                        1 => __('general.active')
                    ]
                ],
                'is_featured'=>[
                    'options'=>[
                        0 => __('general.inactive'),
                        1 => __('general.active')
                    ]
                ],
            ],
        ];
    }

    public static function getFieldsStatic()
    {
        $model = (new static);
        return $model->allFieldsTypes();

    }

    public function deleteFile($field = 'media'){
        if ($this->{$field} != ''){
            if (file_exists(public_path($this->{$field}))){
                unlink(public_path($this->{$field}));
            }
        }
    }

    public function uploadFile($file,$field = 'media'){
        $path = 'uploads/'.date('Y').'/'.date('m').'/';
        $file_name = time().'-'.$file->getClientOriginalName();
        $file->move(public_path($path),$file_name);
        $this->{$field} = $path.$file_name;
        $this->save();
    }


    public function isMedia($field){
        if(in_array($field,$this->getFieldsStatic()['file'])){
            return true;
        }
        return false;
    }

    public function getFirstMediaUrl($field){
        if ($this->{$field} != ''){
            return asset($this->{$field});
        }
        return asset('images/no-image.png');
    }
}
