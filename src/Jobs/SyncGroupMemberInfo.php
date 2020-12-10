<?php

namespace Hudm\Wxa\Jobs;

use Hudm\Wxa\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGroupMemberInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $robotWxid;
    protected $groupWxid;
    protected $memberWxid;

    /**
     * Create a new job instance.
     *
     * @param string $robotWxid
     * @param string $groupWxid
     * @param string $memberWxid
     * @return void
     */
    public function __construct(string $robotWxid, string $groupWxid, string $memberWxid)
    {
        $this->robotWxid = $robotWxid;
        $this->groupWxid = $groupWxid;
        $this->memberWxid = $memberWxid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Artisan::call("MBR:sync {$this->robotWxid} {$this->groupWxid} {$this->memberWxid}");
    }
}
