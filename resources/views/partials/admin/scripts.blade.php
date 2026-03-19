{{-- Vendor --}}
<script src="{{ asset('porto/vendor/jquery/jquery.js') }}"></script>
<script src="{{ asset('porto/vendor/jquery-browser-mobile/jquery.browser.mobile.js') }}"></script>
<script src="{{ asset('porto/vendor/popper/umd/popper.min.js') }}"></script>
<script src="{{ asset('porto/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('porto/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('porto/vendor/common/common.js') }}"></script>
<script src="{{ asset('porto/vendor/nanoscroller/nanoscroller.js') }}"></script>
<script src="{{ asset('porto/vendor/magnific-popup/jquery.magnific-popup.js') }}"></script>
<script src="{{ asset('porto/vendor/jquery-placeholder/jquery.placeholder.js') }}"></script>
<script src="{{ asset('porto/js/examples/examples.modals.js') }}"></script>

{{-- Theme Base --}}
<script src="{{ asset('porto/js/theme.js') }}"></script>

{{-- Theme Custom --}}
<script src="{{ asset('porto/js/custom.js') }}"></script>

{{-- Theme Init --}}
<script src="{{ asset('porto/js/theme.init.js') }}"></script>

{{-- Espaço para scripts extra por página --}}
@stack('scripts')
