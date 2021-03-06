<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 */

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Constant\FrontendConstant;
use Illuminate\Support\Facades\Auth;
use App\Services\Member\Services\RoleService;
use App\Services\Order\Services\OrderService;
use App\Services\Member\Interfaces\RoleServiceInterface;
use App\Services\Order\Interfaces\OrderServiceInterface;

class RoleController extends FrontendController
{
    /**
     * @var RoleService
     */
    protected $roleService;
    /**
     * @var OrderService
     */
    protected $orderService;

    public function __construct(
        RoleServiceInterface $roleService,
        OrderServiceInterface $orderService
    ) {
        parent::__construct();

        $this->roleService = $roleService;
        $this->orderService = $orderService;
    }

    public function index()
    {
        $roles = $this->roleService->all();
        [
            'title' => $title,
            'keywords' => $keywords,
            'description' => $description
        ] = $this->configService->getSeoRoleListPage();

        return v('frontend.role.index', compact('roles', 'title', 'keywords', 'description'));
    }

    // 收银台界面
    public function showBuyPage($id)
    {
        $role = $this->roleService->find($id);
        $title = __('buy role', ['role' => $role['name']]);
        $goods = [
            'id' => $role['id'],
            'thumb' => asset('/images/icons/vip.jpg'),
            'title' => $role['name'],
            'charge' => $role['charge'],
            'label' => $role['name'],
        ];
        $total = $role['charge'];
        $scene = get_payment_scene();
        $payments = get_payments($scene);

        return v('frontend.order.create', compact('title', 'goods', 'total', 'payments', 'scene'));
    }

    // 支付
    public function buyHandler(Request $request)
    {
        $id = $request->input('goods_id');
        $promoCodeId = abs((int)$request->input('promo_code_id', 0));
        $role = $this->roleService->find($id);

        $order = $this->orderService->createRoleOrder(Auth::id(), $role, $promoCodeId);
        if ($order['status'] === FrontendConstant::ORDER_PAID) {
            flash(__('success'), 'success');
            return redirect(route('member.orders'));
        }

        $paymentScene = $request->input('payment_scene');
        $payment = $request->input('payment_sign');

        return redirect(
            route(
                'order.pay',
                [
                    'scene' => $paymentScene,
                    'payment' => $payment,
                    'order_id' => $order['order_id'],
                    // 支付成功后的跳转地址
                    's_url' => route('member'),
                ]
            )
        );
    }
}
