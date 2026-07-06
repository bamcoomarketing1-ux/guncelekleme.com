<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Admin\AdminActionsController;
use App\Http\Controllers\Api\Admin\AdminManageController;
use App\Http\Controllers\Api\Admin\AdminPanelCompatController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ResourceController;
use App\Http\Controllers\Api\Admin\TournamentAdminController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RaffleController;
use App\Http\Controllers\Api\ScratchCardController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\TicketEventController;
use App\Http\Controllers\Api\TicketParticipationController;
use App\Http\Controllers\Api\WheelController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/settings', [ApiController::class, 'settings']);
    Route::post('/settings', [ApiController::class, 'updateSettings'])
        ->middleware(['auth:sanctum', EnsureAdmin::class]);
    Route::post('/admin/login', [ApiController::class, 'adminLogin'])->middleware('throttle:login');
    Route::post('/login', [ApiController::class, 'userLogin'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailSigned'])->name('api.email.verify');
    Route::post('/support/guest-message', [SupportController::class, 'guestMessage']);
    Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

    Route::get('/banners', [PublicController::class, 'banners']);
    Route::get('/sliders', [PublicController::class, 'sliders']);
    Route::get('/sponsors', [PublicController::class, 'sponsors']);
    Route::get('/social-media', [PublicController::class, 'socialMedia']);
    Route::get('/social-media/homepage', [PublicController::class, 'socialMediaHomepage']);
    Route::get('/bonuses', [PublicController::class, 'bonuses']);
    Route::get('/bonuses/featured', [PublicController::class, 'bonusesFeatured']);
    Route::get('/trial-bonuses', [PublicController::class, 'trialBonuses']);
    Route::get('/announcements/banner', [PublicController::class, 'announcementsBanner']);
    Route::get('/popup', [PublicController::class, 'popup']);
    Route::get('/market', [PublicController::class, 'market']);
    Route::get('/raffles', [PublicController::class, 'raffles']);
    Route::get('/raffles/{id}', [PublicController::class, 'raffleShow'])->whereNumber('id');
    Route::get('/tournaments', [PublicController::class, 'tournaments']);
    Route::get('/tournaments/{id}', [PublicController::class, 'tournament']);
    Route::get('/ticket-events', [PublicController::class, 'ticketEvents']);
    Route::get('/ticket-events/homepage', [PublicController::class, 'ticketEventsHomepage']);
    Route::get('/ticket-events/{id}', [TicketEventController::class, 'show'])->whereNumber('id');
    Route::get('/ticket-events/{id}/leaderboard', [TicketEventController::class, 'leaderboard'])->whereNumber('id');
    Route::get('/news', [PublicController::class, 'news']);
    Route::get('/news/{slug}', [PublicController::class, 'newsShow']);
    Route::get('/music', [PublicController::class, 'music']);
    Route::get('/daily-wheel', [PublicController::class, 'dailyWheelPublic']);
    Route::get('/wheel', [WheelController::class, 'show']);
    Route::get('/leaderboard', [PublicController::class, 'leaderboard']);
    Route::get('/special-odds', [PublicController::class, 'specialOdds']);
    Route::get('/special-odds/history', [PublicController::class, 'specialOddsHistory']);
    Route::get('/link-items', [PublicController::class, 'linkItems']);
    Route::get('/xp-rewards', [PublicController::class, 'xpRewards']);

    Route::post('/analytics/visit', [AnalyticsController::class, 'visit']);
    Route::post('/analytics/ping', [AnalyticsController::class, 'ping']);
    Route::post('/sponsors/{id}/click', [AnalyticsController::class, 'sponsorClick'])->whereNumber('id');

    Route::middleware(['auth:sanctum', EnsureAdmin::class])->prefix('admin')->group(function () {
        Route::get('/me', [ApiController::class, 'adminMe']);
        Route::post('/logout', [AuthController::class, 'adminLogout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/statistics', [DashboardController::class, 'index']);

        Route::get('/users', [ApiController::class, 'adminUsers']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::post('/users/verify-all-emails', [AdminUserController::class, 'verifyAllEmails']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::patch('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

        Route::get('/admins', [AdminManageController::class, 'index']);
        Route::post('/admins', [AdminManageController::class, 'store']);
        Route::put('/admins/{id}', [AdminManageController::class, 'update']);
        Route::patch('/admins/{id}', [AdminManageController::class, 'update']);
        Route::delete('/admins/{id}', [AdminManageController::class, 'destroy']);

        Route::get('/support-history', [SupportController::class, 'adminHistory']);
        Route::get('/support-stats', [SupportController::class, 'adminStats']);
        Route::post('/support-reply', [SupportController::class, 'adminReply']);
        Route::get('/telegram', [TelegramController::class, 'show']);
        Route::post('/telegram', [TelegramController::class, 'update']);
        Route::put('/telegram', [TelegramController::class, 'update']);

        $panel = AdminPanelCompatController::class;
        Route::get('/link-manager', [$panel, 'linkManagerIndex']);
        Route::post('/link-manager/bulk-replace', [$panel, 'linkManagerBulkReplace']);
        Route::get('/level-rewards', [$panel, 'levelRewardsIndex']);
        Route::post('/level-rewards', [$panel, 'levelRewardsStore']);
        Route::put('/level-rewards/{id}', [$panel, 'levelRewardsUpdate'])->whereNumber('id');
        Route::delete('/level-rewards/{id}', [$panel, 'levelRewardsDestroy'])->whereNumber('id');
        Route::get('/market/orders', [$panel, 'marketOrders']);
        Route::get('/market-orders', [$panel, 'marketOrders']);
        Route::post('/market/orders/{id}/approve', [AdminActionsController::class, 'approveMarketOrder'])->whereNumber('id');
        Route::post('/market/orders/{id}/reject', [AdminActionsController::class, 'rejectMarketOrder'])->whereNumber('id');
        Route::get('/scratch-card/settings', [$panel, 'scratchSettingsShow']);
        Route::put('/scratch-card/settings', [$panel, 'scratchSettingsUpdate']);
        Route::get('/scratch-card/history', [$panel, 'scratchHistory']);
        Route::get('/support/conversations', [$panel, 'supportConversations']);
        Route::get('/support/conversations/{userId}', [$panel, 'supportConversation'])->whereNumber('userId');
        Route::delete('/support/conversations/{userId}', [$panel, 'supportConversationDestroy'])->whereNumber('userId');
        Route::get('/support/stats', [$panel, 'supportStats']);
        Route::get('/telegram/settings', [$panel, 'telegramSettingsShow']);
        Route::put('/telegram/settings', [$panel, 'telegramSettingsUpdate']);
        Route::post('/telegram/set-webhook', [$panel, 'telegramSetWebhook']);
        Route::post('/telegram/delete-webhook', [$panel, 'telegramDeleteWebhook']);
        Route::post('/telegram/broadcast', [$panel, 'telegramBroadcast']);

        Route::get('/scratch-card', [ScratchCardController::class, 'adminIndex']);
        Route::post('/scratch-card', [ScratchCardController::class, 'adminStore']);
        Route::put('/scratch-card/{id}', [ScratchCardController::class, 'adminUpdate']);
        Route::delete('/scratch-card/{id}', [ScratchCardController::class, 'adminDestroy']);

        Route::get('/ticket-participations', [TicketParticipationController::class, 'adminIndex']);
        Route::get('/ticket-events/{id}/participations', [TicketParticipationController::class, 'adminEventParticipations'])->whereNumber('id');
        Route::get('/ticket-events/{id}/users/{userId}/tickets', [TicketParticipationController::class, 'adminUserTickets'])->whereNumber(['id', 'userId']);
        Route::post('/ticket-events/{id}/users/{userId}/tickets/add', [TicketParticipationController::class, 'addTickets'])->whereNumber(['id', 'userId']);
        Route::post('/ticket-events/{id}/users/{userId}/tickets/remove', [TicketParticipationController::class, 'removeTickets'])->whereNumber(['id', 'userId']);
        Route::delete('/ticket-events/{id}/tickets/{ticketId}', [TicketParticipationController::class, 'deleteTicket'])->whereNumber(['id', 'ticketId']);
        Route::get('/tickets/search-user', [TicketParticipationController::class, 'searchUser']);
        Route::get('/tickets/search-ticket', [TicketParticipationController::class, 'searchTicket']);
        Route::get('/wheel/history', [WheelController::class, 'adminHistory']);

        Route::post('/notifications/broadcast', [AdminNotificationController::class, 'broadcast']);

        Route::post('/announcements/{id}/toggle-active', [AdminActionsController::class, 'toggleAnnouncement']);
        Route::post('/bonuses/{id}/toggle-featured', [AdminActionsController::class, 'toggleBonusFeatured']);
        Route::post('/promocodes/{id}/toggle', [AdminActionsController::class, 'togglePromocode']);
        Route::get('/promocodes/{id}/usages', [AdminActionsController::class, 'promocodeUsages']);
        Route::get('/special-odds/{id}/bets', [AdminActionsController::class, 'specialOddBets']);
        Route::post('/special-odds/{id}/settle', [AdminActionsController::class, 'settleSpecialOdd']);
        Route::post('/ticket-events/{id}/toggle-homepage', [AdminActionsController::class, 'toggleTicketEventHomepage']);
        Route::post('/ticket-events/{id}/end', [AdminActionsController::class, 'endTicketEvent']);
        Route::get('/ticket-events/{id}/requests', [TicketEventController::class, 'adminRequests'])->whereNumber('id');
        Route::post('/ticket-requests/{id}/approve', [AdminActionsController::class, 'approveTicketRequest']);
        Route::post('/ticket-requests/{id}/reject', [AdminActionsController::class, 'rejectTicketRequest']);
        Route::post('/market-orders/{id}/approve', [AdminActionsController::class, 'approveMarketOrder']);
        Route::post('/market-orders/{id}/reject', [AdminActionsController::class, 'rejectMarketOrder']);
        Route::post('/raffles/{id}/draw', [AdminActionsController::class, 'drawRaffle']);
        Route::post('/raffles/{id}/pick', [AdminActionsController::class, 'drawRaffle']);
        Route::post('/users/{id}/toggle-active', [AdminActionsController::class, 'toggleUserActive']);
        Route::post('/users/{id}/toggle-moderator', [AdminActionsController::class, 'toggleUserModerator']);
        Route::post('/users/{id}/balance', [AdminActionsController::class, 'updateUserBalance']);
        Route::post('/users/{id}/xp', [AdminActionsController::class, 'updateUserXp']);
        Route::get('/users/{id}/history', [AdminActionsController::class, 'userHistory']);
        Route::post('/users/{id}/telegram/disconnect', [AdminActionsController::class, 'disconnectTelegram']);

        Route::post('/tournaments/{id}/match', [TournamentAdminController::class, 'updateMatch']);
        Route::put('/tournaments/{id}/bracket', [TournamentAdminController::class, 'updateBracket']);
        Route::get('/tournaments/{id}/detail', [TournamentAdminController::class, 'show'])->whereNumber('id');
        Route::post('/tournaments/{id}/participants', [TournamentAdminController::class, 'addParticipant'])->whereNumber('id');
        Route::delete('/tournaments/{id}/participants/{participantId}', [TournamentAdminController::class, 'removeParticipant'])->whereNumber(['id', 'participantId']);
        Route::post('/tournaments/{id}/start', [TournamentAdminController::class, 'start'])->whereNumber('id');
        Route::post('/tournaments/{id}/matches/{matchId}/winner', [TournamentAdminController::class, 'setMatchWinner'])->whereNumber(['id', 'matchId']);
        Route::post('/popups/{id}/toggle', [AdminActionsController::class, 'togglePopup'])->whereNumber('id');

        foreach (array_keys(config('platform.admin')) as $resource) {
            Route::get("/{$resource}", fn () => app(ResourceController::class)->index($resource));
            Route::post("/{$resource}", fn (Request $request) => app(ResourceController::class)->store($request, $resource));
            Route::post("/{$resource}/reorder", fn (Request $request) => app(ResourceController::class)->reorder($request, $resource));
            Route::get("/{$resource}/{id}", fn (int $id) => app(ResourceController::class)->show($resource, $id))->whereNumber('id');
            // Multipart güncellemeler frontend'de POST + _method=PUT ile gelir; spoofing bazı sunucularda bozulabiliyor.
            Route::post("/{$resource}/{id}", fn (Request $request, int $id) => app(ResourceController::class)->update($request, $resource, $id))->whereNumber('id');
            Route::put("/{$resource}/{id}", fn (Request $request, int $id) => app(ResourceController::class)->update($request, $resource, $id))->whereNumber('id');
            Route::patch("/{$resource}/{id}", fn (Request $request, int $id) => app(ResourceController::class)->update($request, $resource, $id))->whereNumber('id');
            Route::delete("/{$resource}/{id}", fn (int $id) => app(ResourceController::class)->destroy($resource, $id));
        }
    });

    Route::middleware(['auth:sanctum', EnsureUser::class])->group(function () {
        Route::get('/user', [ApiController::class, 'currentUser']);
        Route::post('/user/avatar', [AccountController::class, 'uploadAvatar']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

        Route::get('/user/sponsors', [AccountController::class, 'userSponsors']);
        Route::post('/user/sponsors/{id}', [AccountController::class, 'connectUserSponsor'])->whereNumber('id');
        Route::get('/participation-history', [AccountController::class, 'participationHistory']);
        Route::get('/account/telegram/status', [AccountController::class, 'telegramStatus']);
        Route::post('/account/telegram/generate-code', [AccountController::class, 'telegramGenerateCode']);

        Route::get('/account', [AccountController::class, 'account']);
        Route::get('/account/profile', [AccountController::class, 'profile']);
        Route::get('/account/history', [AccountController::class, 'history']);
        Route::get('/account/notifications', [AccountController::class, 'notifications']);
        Route::get('/account/tickets', [AccountController::class, 'tickets']);
        Route::get('/account/sessions', [AccountController::class, 'sessions']);
        Route::get('/account/promo', [AccountController::class, 'promoHistory']);
        Route::get('/account/sponsors', [AccountController::class, 'sponsors']);
        Route::post('/account/promo/use', [AuthController::class, 'usePromo']);
        Route::post('/account/verify', [AccountController::class, 'verifyAccount']);
        Route::post('/market/order', [AccountController::class, 'marketOrder']);
        Route::post('/market/{id}/purchase', [AccountController::class, 'marketPurchase'])->whereNumber('id');
        Route::get('/market/history', [AccountController::class, 'marketHistory']);
        Route::post('/raffles/join', [RaffleController::class, 'join']);
        Route::post('/raffles/{id}/join', [RaffleController::class, 'joinById'])->whereNumber('id');
        Route::get('/raffles/my-tickets', [RaffleController::class, 'myTickets']);
        Route::post('/ticket-participations', [TicketParticipationController::class, 'join']);
        Route::post('/ticket-events/{id}/request', [TicketEventController::class, 'request'])->whereNumber('id');
        Route::post('/daily-wheel/spin', [WheelController::class, 'spin']);
        Route::get('/wheel/history', [WheelController::class, 'userHistory']);
        Route::post('/wheel/spin', [WheelController::class, 'spinCompat']);
        Route::get('/scratch-card', [ScratchCardController::class, 'showPublic']);
        Route::post('/scratch-card/purchase', [ScratchCardController::class, 'purchase']);
        Route::post('/scratch-card/{id}/reveal', [ScratchCardController::class, 'reveal'])->whereNumber('id');
        Route::post('/scratch-card/play', [ScratchCardController::class, 'play']);
        Route::get('/scratch-card/daily-stats', [ScratchCardController::class, 'dailyStats']);
        Route::post('/special-odds/bet', [AccountController::class, 'placeSpecialOddBet']);
        Route::get('/special-odds/my-bets', [AccountController::class, 'mySpecialOddBets']);
        Route::get('/telegram/verify-link', [TelegramController::class, 'verificationLink']);

        Route::get('/user/wallets', [AccountController::class, 'wallets']);
        Route::post('/user/wallets', [AccountController::class, 'updateWallets']);
        Route::get('/notifications', [AccountController::class, 'notifications']);
        Route::get('/notifications/unread-count', [AccountController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [AccountController::class, 'readAllNotifications']);
        Route::post('/notifications/{id}/read', [AccountController::class, 'readNotification']);
        Route::delete('/notifications/{id}', [AccountController::class, 'deleteNotification']);
        Route::get('/history', [AccountController::class, 'history']);
        Route::get('/sessions', [AccountController::class, 'sessions']);
        Route::delete('/sessions/all', [AccountController::class, 'revokeAllSessions']);
        Route::delete('/sessions/{id}', [AccountController::class, 'revokeSession'])->whereNumber('id');
        Route::get('/games/session-log', [GameController::class, 'allSessions']);

        Route::get('/support/messages', [SupportController::class, 'messages']);
        Route::post('/support/messages', [SupportController::class, 'send']);
        Route::delete('/support/messages', [SupportController::class, 'clearMessages']);

        Route::get('/games/mines', [GameController::class, 'minesConfig']);
        Route::get('/games/dice', [GameController::class, 'diceConfig']);
        Route::get('/games/mines/active', [GameController::class, 'minesActive']);
        Route::get('/games/mines/daily-stats', [GameController::class, 'minesDailyStats']);
        Route::get('/games/dice/daily-stats', [GameController::class, 'diceDailyStats']);
        Route::get('/games/blackjack', [GameController::class, 'blackjackConfig']);
        Route::get('/games/blackjack/active', [GameController::class, 'blackjackActive']);
        Route::post('/games/mines/start', [GameController::class, 'startMines']);
        Route::post('/games/mines/reveal', [GameController::class, 'revealMines']);
        Route::post('/games/mines/cashout', [GameController::class, 'cashoutMines']);
        Route::post('/games/dice/play', [GameController::class, 'playDice']);
        Route::post('/games/blackjack/play', [GameController::class, 'playBlackjack']);
        Route::post('/games/blackjack/hit', [GameController::class, 'hitBlackjack']);
        Route::post('/games/blackjack/stand', [GameController::class, 'standBlackjack']);
        Route::post('/games/blackjack/double', [GameController::class, 'doubleBlackjack']);
    });
});
