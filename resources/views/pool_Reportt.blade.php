@include('includes.headcss')
@include('includes.header')
@include('includes.sideNavigation')

<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row bg-title">
            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                <h4 class="page-title">Pool Report</h4>
            </div>
        </div>
        
        <div class="row">
            <div class="white-box">
                <div class="panel-body">
                    <div>
                        <ul class="nav nav-tabs member-tab" role="tablist">
                            <li role="presentation" class="active"><a href="#all_members" aria-controls="all_members"
                                    role="tab" data-toggle="tab">Pool Report</a></li>
                        </ul>

                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="all_members">
                                <div class="d-flex flex-wrap search-form export-form">
                                    <form action="{{route('pool_Reportt')}}" method="post" class="mb-0 col-xs-12 col-md-8">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input id="hash" name="hash" @if(isset($data['hash'])) value="{{$data['hash']}}" @endif type="text" class="form-control" placeholder="Transaction Hash Or Wallet Address" autocomplete="off">
                                            </div>
                                            <div class="form-group">
                                                <select name="pool_type" class="form-control">
                                                    <option value="">Select Type</option>
                                                    <option value="FOUNDER" @if(isset($data['pool_type'])) @if($data['pool_type'] == 'FOUNDER') selected @endif @endif>FOUNDER</option>
                                                    <option value="LEADER" @if(isset($data['pool_type'])) @if($data['pool_type'] == 'LEADER') selected @endif @endif>LEADER</option>
                                                    <option value="FOUNDER-POOL" @if(isset($data['pool_type'])) @if($data['pool_type'] == 'FOUNDER-POOL') selected @endif @endif>FOUNDER-POOL</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn waves-effect waves-light btn-success">Search</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                @if(isset($data['data']))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <h5 class="card-title mb-4">All Members</h5>
                                                <div class="table-responsive">
                                                    <table class="table no-wrap user-table mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Sr</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Wallet Address</th>
                                                                <th scope="col" class="border-0 fs-4 font-weight-medium">Pool</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($data['data'] as $key => $value)
                                                                <tr>
                                                                    <td>{{$key+1}}</td>
                                                                    @if ($value->pool=='FOUNDER'||$value->pool=='FOUNDER-POOL'||$value->pool=='LEADER')
                                                                        <td><h5 class="font-weight-medium mb-1">{{ substr($value->wallet_address, 0, 6) . '...' . substr($value->wallet_address, -6) }}</h5></td>
                                                                    @else
                                                                        <td></td>
                                                                    @endif
                                                                    <td><span class="badge badge-inverse fs-4">{{$value->pool}}</span></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>

                                                    <div class="col-sm-12 col-md-7">
                                                        <div class="dataTables_paginate paging_simple_numbers" id="example_paginate">
                                                        <ul class="pagination">
                                                                @foreach($data['data']->toArray()['links'] as $value)
                                                                    @if($value['url'])
                                                                        <li class="paginate_button page-item {{ $value['active'] ? 'active' : '' }}">
                                                                            <a href="{{ $value['url'] }}&pool_type={{ $data['pool_type'] }}&hash={{ $data['hash'] }}"
                                                                            class="page-link">
                                                                                {!! $value['label'] !!}   {{-- use {!! !!} instead of {{ }} --}}
                                                                            </a>
                                                                        </li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('includes.footerJs')
    <script>
        $(document).ready(function () {
            $('#example').DataTable();
        });
    </script>
    <script>
        $(function() {
            $('.start-date').datepicker({ dateFormat: 'dd-mm-yy' });
            $('.end-date').datepicker({ dateFormat: 'dd-mm-yy' });
        });
    </script>
    @include('includes.footer')