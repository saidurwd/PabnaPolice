<div class="col-lg-4 offset-lg-0 col-md-11 offset-md-1 col-11 offset-1 no-padding-right relative">
                                            <div class="absolute z-index-400" style="left:45%; top:40%">
                                                <img src="{{ asset('assets/images/send.gif') }}" style="display:none" class="button_1right_iconImage" width="60" >
                                            </div>
                                            <h1 class="white margin_bottom_10 size-7-vh google_great_vibes" style="padding-left: 15px;">Quick Contact</h1>
                                            <div class="sucessMessage alert-dismissible alert alert-success fade show animated slideInRight" style="display:none;"></div>
                                            <div class="errorMessage alert alert-warning fade show alert-dismissible animated slideInRight" style="display:none;"></div>
                                            <input type="hidden" id="csrf-token" value="{{ csrf_token() }}">
                                            <div class="col-xl-6 col-lg-6 col-md-12 relative name margin_bottom_10">
                                                <input type="text" name="name" placeholder="Name" required class="form-control no-border-radius margin_bottom_10" id="name" aria-label="Name">
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-12 relative email margin_bottom_10">
                                                <input type="email" name="email" placeholder="Email" required class="form-control no-border-radius margin_bottom_10" id="email" aria-label="Email">
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-12 relative phone margin_bottom_10">
                                                <input type="tel" name="phone" placeholder="Telephone Number" required class="form-control no-border-radius margin_bottom_10" id="phoneNumber" aria-label="Telephone number">
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-12 relative nid margin_bottom_10">
                                                <input type="number" name="nid" placeholder="NID" class="form-control no-border-radius margin_bottom_10" id="nid" aria-label="NID">
                                            </div>
                                            <div class="col-xl-12 col-lg-12 col-md-12 relative message">
                                                <textarea name="message" placeholder="You Text here..." rows="5" class="form-control no-border-radius margin_bottom_10" id="message" aria-label="Message"></textarea>
                                                <button type="submit" name="submit" value="submit" class="btn button_1 float-end margin_bottom_25" id="submit" data-url="{{ route('front.feedback') }}">Send <i class="fa fa-angle-double-right"></i></button>
                                            </div>
                                        </div>
