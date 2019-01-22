<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Order;

class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, $delay)
    {
        $this->order = $order;
        
        // 设置延迟时间， delay() 方法的参数代表多少秒后 执行
        $this->delay($delay);
    }
    
    // 定义这个任务类具体的执行逻辑
    // 当队列处理器从队列 中取出任务时，会调用 handle() 方法
    public function handle()
    {
        // 判断对应的订单是否已经被支付
        // 如果已经被支付则不需要关闭订单，直接退出
        if ($this->order->paid_at)
        {
        	return;
        }
        
        // 通过事务执行 sql
        \DB::transaction(function () {
        	// 将订单的 closed 字段标记为 true， 即关闭订单
        	$this->order->update(['closed' => true]);
        	// 循环遍历订单中的商品SKU，将订单的数量加回到 SKU 的库存中
        	foreach ($this->order->items as $item)
        	{
        		$item->productSku->addStock($this->amount);
        	}
        		
        });
    }
}
