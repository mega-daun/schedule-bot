<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\User as ObjectsUser;

/**
 * @property User|null $user
 *
 * @method void setUser(\Telegram\Bot\Objects\User $from)
 */
trait HasUser
{
    /**
     * related user
     */
    protected ?User $user;

    /**
     * @param  ObjectsUser  $from  Data that we recieve by telegram's API
     * @return void
     */
    private function setUser(ObjectsUser $from)
    {
        try {
            $this->user = User::findOrFail(['id' => $from->id])->first();
        } catch (ModelNotFoundException $e) {
            $this->user = User::create([
                'id' => $from->id,
                'first_name' => $from->firstName,
                'language_code' => $from->languageCode,
                'username' => $from->username,
            ]);
        } catch (\Exception $e) {
            Log::error(
                $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'from' => $from,
                ]
            );
        }
    }
}
