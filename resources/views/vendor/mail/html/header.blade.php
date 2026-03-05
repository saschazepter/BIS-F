@props(['url'])
<tr>
    <td>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="header" style="text-align: right;">
                    <a href="{{ $url }}" style="display: inline-block;">
                        <img src="{{ $url  }}/images/icons/logo512.png" class="logo" alt="Laravel Logo">
                    </a>
                </td>
                <td class="header" style="text-align: left;">
                    <a href="{{ $url }}" style="display: inline-block;">
                        {!! $slot !!}
                    </a>
                </td>
            </tr>
        </table>
    </td>
</tr>
