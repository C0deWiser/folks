<?php

namespace Codewiser\Rpac\Console\Commands;

use Codewiser\Rpac\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RpacCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rpac {act}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создаёт роль и привязывает/создаёт пользователя';

    protected $userModel;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->userModel = config('rpac.models.user');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $acts = explode(':', $this->argument('act'));


        if (!count($acts)) {
            $info = 'Непонятный аргумент (' . $this->argument('act') . '). Нужно так: `php artisan rpac su:1` или `php artisan rpac admin:email@example.com:password`';
        } else {

            $roleSlug = array_shift($acts);
            $userSlug = array_shift($acts);
            $userPass = array_shift($acts);

            if (!$userSlug) {
                $info = 'Нужно указать EMAIL или ID. Например: `php artisan rpac su:1` или `php artisan rpac admin:email@example.com:password`';
            } else {
                $error = false;
                $user = null;
                $userEmail = filter_var($userSlug, FILTER_VALIDATE_EMAIL);


                if ($userEmail) {
                    $user = $this->userModel::where('email', $userEmail)->first();
                } else {
                    $user = $this->userModel::find($userSlug);
                    if (!$user) {
                        $error = 'Не найден пользователь по ID (или неверный формат EMAIL). Попробуйте указать EMAIL: `php artisan rpac su:email@example.com`';
                    }
                }

                if ($error) {
                    $info = $error;
                } else {

                    $role = Role::query()->firstOrCreate(['id' => $roleSlug]);

                    if (!$user) {
                        // Если не указан пароль - генерируем
                        if (!$userPass) $userPass = Str::random(6);

                        $user = $this->userModel::create(['name' => $userEmail, 'email' => $userEmail, 'password' => bcrypt($userPass), 'api_token' => Str::random(60)]);
                        $info = 'Cоздан';
                    } else {
                        $info = 'Найден';

                        // Если указан пароль - шифруем и сохраняем
                        if ($userPass) {
                            $user->password = bcrypt($userPass);
                            $user->save();
                        }

                    }

                    $info .= ' пользователь с ID: [' . $user->id . '] и EMAIL: [' . $user->email . ']. ';
                    if ($userPass) {
                        $info .= ' Пароль пользователя: [' . $userPass . ']. ';
                    }

                    if (!$user->roles) {
                        $info = 'Модель User должна использовать трейт Rpacable (или Wfacable) и иметь protected $with = [roles]';
                    } else {
                        // Если нет токена - создаём
                        if (!$user->api_token) {
                            $user->api_token = Str::random(60);
                            $user->save();
                        }

                        if ($user->roles->contains('id', $role->id)) {
                            $info .= 'Роль [' . $role->slug . '] привязана ранее';
                        } else {
                            $user->roles()->attach($role->id);
                            $info .= 'Роль [' . $role->slug . '] привязана к пользователю';
                        }

                    }
                }
            }
        }

        $this->info($info);
    }
}
