@extends('merchant.template.main')

@section('content')
    
    <div class="content-wrapper admin-content-wrapper">
    <section class="content">
      <div class="container-fluid merchant-products-container">
          <merchant-product-edit 
          :product="{{ $product }}"
          ></merchant-product-edit>   
      </div>
  </section>
</div>

@endsection
