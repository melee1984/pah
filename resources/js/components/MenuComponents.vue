<template>
    <section>
        <ul>
            <li><a href="/be-partner">Be our Partner</a></li>
            <li><a href="/restaurants">Restaurants</a></li>
            <li><a href="/flowerstore">Flowerstore</a></li>
            <li><a href="/request/booking">Delivery Rates</a></li>
        </ul>
    </section>
</template>

<script>


    export default {
        data() {
            return {
                isLoggedIn: false,
            }
        },
        mounted() {

            this.isLoggedIn = isLogged;

            console.log("Register Form Mouted");
        },
        methods: { 
            popRegister: function() {
                $('#myModalRegister').modal();
            }
        }
    }
</script>

