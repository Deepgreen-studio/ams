<?php

namespace App\Domains\Content\Middleware;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Services\CmsApiKeyService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsPrivateAccess
{
    public function __construct(
        private readonly CmsApiKeyService $cmsApiKeyService
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user() ?? Auth::guard('sanctum')->user();

        if ($user instanceof User) {
            Auth::setUser($user);
            $request->setUserResolver(static fn () => $user);

            if ($user->can(ContentPermission::VIEW)) {
                return $next($request);
            }

            abort(403, 'Forbidden.');
        }

        $plain = $this->extractApiKey($request);
        $apiKey = $this->cmsApiKeyService->findValidByPlainText($plain);

        if ($apiKey) {
            $request->attributes->set('cms_api_key', $apiKey);

            return $next($request);
        }

        abort(401, 'Unauthenticated.');
    }

    protected function extractApiKey(Request $request): ?string
    {
        $header = $request->header('X-CMS-Api-Key');
        if (filled($header)) {
            return trim((string) $header);
        }

        $authorization = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(cms_.+)$/i', $authorization, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
