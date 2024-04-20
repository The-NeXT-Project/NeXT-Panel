<div class="col-sm-12 col-md-12">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">FIDO
                {if $fido_devices!==null}
                    <span class="badge bg-green text-green-fg">已启用</span>
                {else}
                    <span class="badge bg-red text-red-fg">未启用</span>
                {/if}
            </h3>
            <p class="card-subtitle">FIDO2 是一种基于公钥加密的身份验证标准，可以提供更安全的登录方式。支持Yubikey等硬件安全密钥。</p>
            {if $fido_devices!==null}
            <div class="row row-cols-1 row-cols-md-4 g-4">
                {foreach $fido_devices as $device}
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">{$device->name|default:'未命名'}</h5>
                                <p class="card-text">添加时间: {$device->created_at}</p>
                                <p class="card-text">上次使用: {$device->used_at|default:'从未使用'}</p>
                                <button class="btn btn-danger"
                                        hx-delete="/user/fido_reg/{$device->id}"
                                        hx-swap="none"
                                >删除
                                </button>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
            {/if}
        </div>
        <div class="card-footer">
            <div class="d-flex">
                <button class="btn btn-primary ms-auto" id="fidoReg">
                    注册 FIDO 设备
                </button>
            </div>
        </div>
    </div>
</div>
{literal}
    <script>
        document.getElementById('fidoReg').addEventListener('click', async () => {
            const resp = await fetch('/user/fido_reg');
            let attResp;
            try {
                attResp = await startRegistration(await resp.json());
            } catch (error) {
                $('#error-message').text(error.message);
                $('#fail-dialog').modal('show');
                throw error;
            }
            attResp.name = prompt("请输入设备名称:");
            const verificationResp = await fetch('/user/fido_reg', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(attResp),
            });

            const verificationJSON = await verificationResp.json();
            if (verificationJSON.ret === 1) {
                $('#success-message').text(verificationJSON.msg);
                $('#success-dialog').modal('show');
                location.reload();
            } else {
                $('#error-message').text(verificationJSON.msg);
                $('#fail-dialog').modal('show');
            }
        });
    </script>
{/literal}