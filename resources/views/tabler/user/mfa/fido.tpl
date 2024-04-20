<div class="col-sm-12 col-md-6">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">FIDO
                {if $fido_devices===[]}
                    <span class="badge bg-green text-green-fg">已启用</span>
                {else}
                    <span class="badge bg-red text-red-fg">未启用</span>
                {/if}
            </h3>
            <p class="card-subtitle">FIDO 是一种基于生物识别的一次性密码算法，可以使用支持 FIDO2 的设备进行验证</p>
        </div>
        <div class="card-footer">
            {if $fido_devices===[]}
                <button class="btn btn-red ms-auto"
                        hx-delete="/user/totp" hx-swap="none">
                    禁用
                </button>
            {else}
                <button class="btn btn-primary ms-auto"
                        hx-post="/user/totp" hx-swap="none">
                    启用
                </button>
            {/if}
        </div>
    </div>
</div>