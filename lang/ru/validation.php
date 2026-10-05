<?php

return [
    'accepted' => 'Необходимо принять поле :attribute.',
    'array' => 'Поле :attribute должно быть массивом.',
    'boolean' => 'Поле :attribute должно быть true или false.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'date' => 'Поле :attribute должно быть корректной датой.',
    'digits' => 'Поле :attribute должно содержать :digits цифр.',
    'email' => 'Поле :attribute должно быть корректным адресом электронной почты.',
    'exists' => 'Выбранное значение поля :attribute некорректно.',
    'in' => 'Выбранное значение поля :attribute некорректно.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'regex' => 'Поле :attribute имеет неверный формат.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'string' => 'Поле :attribute должно быть строкой.',
    'unique' => 'Такое значение поля :attribute уже существует.',
    'between' => [
        'numeric' => 'Поле :attribute должно быть от :min до :max.',
        'string' => 'Длина поля :attribute должна быть от :min до :max символов.',
    ],
    'gt' => [
        'numeric' => 'Поле :attribute должно быть больше :value.',
    ],
    'max' => [
        'numeric' => 'Поле :attribute не должно быть больше :max.',
        'string' => 'Поле :attribute не должно быть длиннее :max символов.',
    ],
    'min' => [
        'numeric' => 'Поле :attribute должно быть не меньше :min.',
        'string' => 'Поле :attribute должно содержать не меньше :min символов.',
    ],
    'size' => [
        'numeric' => 'Поле :attribute должно быть равно :size.',
        'string' => 'Поле :attribute должно содержать :size символа.',
    ],

    'custom' => [
        'phone' => [
            'required' => 'Введите номер телефона.',
            'regex' => 'Введите номер телефона полностью.',
        ],
        'code' => [
            'required' => 'Введите код из SMS.',
            'size' => 'Код состоит из 4 цифр.',
        ],
    ],

    'attributes' => [
        'phone' => 'номер телефона',
        'email' => 'email',
        'code' => 'код',
        'name' => 'имя',
        'amount' => 'сумма',
        'comment' => 'комментарий',
        'target_amount' => 'целевая сумма',
        'target_date' => 'дата цели',
    ],
];
