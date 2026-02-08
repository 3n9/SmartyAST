{assign var='title' value='Hello'}
{include 'header.tpl'}
{if $user.active}
  {$user.name|escape:'html'}
{else}
  Guest
{/if}
