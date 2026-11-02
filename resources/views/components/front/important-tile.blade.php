@props(['link'])
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 margin_bottom_20  {{ $link->is_extra ? 'hide_box' : '' }}">
                                    <div class=" col-md-12 bg_gray_light  text-center box8" style="width: 100%;">
                                        <a href="{{ $link->href() }}" target="_blank" class="block" style="height: 100px;">
                                            <div class="row">
                                                <div class="col-lg-2 col-md-3 col-sm-3 col-xs-2 hide_overflow" style="height: 100px; line-height: 80px;" >
                                                    <img src="{{ $link->imageUrl() ?? asset('assets/images/icons/Government_Seal_of_Bangladesh.png') }}" onerror="this.src='{{ asset('assets/images/icons/Government_Seal_of_Bangladesh.png') }}'" width="40" alt="">
                                                </div>
                                                <div class="col-lg-10 col-md-9 col-sm-9 col-xs-10 text-left hide_overflow black" style="height: 80px; display:table-cell; vertical-align: middle !important;">
                                                    <table class="height_full width_full">
                                                        <tr>
                                                            <td valign="middle">
                                                                <span class="size-16">{{ $link->title }}</span><br>
                                                                <span class="size-12 gray_dark">{{ $link->subtitle }}</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            