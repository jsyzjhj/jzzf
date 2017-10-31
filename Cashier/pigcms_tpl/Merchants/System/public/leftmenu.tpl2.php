<nav role="navigation" class="navbar-default navbar-static-side">
    <div class="sidebar-collapse">
        <ul id="side-menu" class="nav metismenu">
            <li class="nav-header">
                <div class="dropdown profile-element"> <span>
                        <i class="fa fa-cogs" style="font-size:60px;color:#1ab394;"></i>
                    </span>
                    <a href="javascript:;" class="dropdown-toggle">
                        <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold">{pg:$adminuser.account}</strong>
                            </span> <span class="text-muted text-xs block">收银台管理后台</span> </span> </a>
                </div>
                <div class="logo-element" style="text-align: center;">
                    <i class="fa fa-cogs" style="font-size:50px;color:#1ab394;"></i>

                </div>
            </li>

           
            <li {pg:if ROUTE_CONTROL eq 'pay' && ROUTE_ACTION=='ModifyPwd'} class="active" {pg:/if}>
                <a href="/merchants.php?m=System&c=pay&a=ModifyPwd"><i class="fa fa-unlock-alt"></i> <span class="nav-label">账户设置</span><span class="label label-info pull-right"></span></a>
            </li>
             <li {pg:if ROUTE_CONTROL eq 'pay' && ROUTE_ACTION=='config'} class="active" {pg:/if}>
                <a href="/merchants.php?m=System&c=pay&a=config"><i class="fa fa-unlock-alt"></i> <span class="nav-label">支付配置</span><span class="label label-info pull-right"></span></a>
            </li>
            <li {pg:if ROUTE_CONTROL eq 'pay' && ROUTE_ACTION=='rebate'} class="active" {pg:/if}>
                <a href="/merchants.php?m=System&c=pay&a=rebate"><i class="fa fa-unlock-alt"></i> <span class="nav-label">费率配置</span><span class="label label-info pull-right"></span></a>
            </li>
            <li {pg:if ROUTE_CONTROL eq 'pay' && ROUTE_ACTION=='TemplateImage'} class="active" {pg:/if}>
                <a href="/merchants.php?m=System&c=pay&a=TemplateImage"><i class="fa fa-unlock-alt"></i> <span class="nav-label">模版图片上传</span><span class="label label-info pull-right"></span></a>
            </li>
        </ul>

    </div>
</nav>