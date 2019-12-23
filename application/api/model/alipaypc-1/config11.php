<?php
$config = array (
		//应用ID,您的APPID。
		'app_id' => "2018010801693243",

		//商户私钥
		'merchant_private_key' => "MIIEpAIBAAKCAQEA0tVTTdqkRs9jpgQn94q/DoKqia0cK8uGDQf7BsAdLpOMn21GracNp1xYkR/LEHt9spQ4xcl2iGs1H0ZjYZXD12fwO1vjBPdN3IykJQkgMTVXnhh83qaMepA/i70SOzKdlBTYk8wUxe39avtIvVyvClq7Ay0711hyJ+U5V51lHJ4bennidT5rLX47eDmbi7K34UdvJ5Bq27QJJuQ18m7gF6aBKgesIgM2Q5rjG1wTmp+PT+Ac3frV85EjH6r+e+pCANbZU+r1R0ubryCUbsN+zKEtRWlQnpbD1w7ZzieGyMmAzhuvqvRVQhftU7E4wi0Id9UmiAc8fsNs1aQl6qstWwIDAQABAoIBAQCruwOd2MgSvX7HjX8YjTVOlnRerFjT+3V3S9bXCsk6VQkoGxSFAhuHBRtHKoR3Kn+aZE1SHeWk/N5KWUU+CYV6AbmWQCRMuTv8t7pESIimTHLP3dZobXTjqHd6VUlyWqosIka/LG6HQnE3IPOA+uvBHWJTFhc2M17YzEfG6/eeNFeXkmVnq8y1mo0iXlRnPEN5b/nr2LKy7uFNPJX3s9khmrziQop2Aw74IZ9fOxGYfxpGoDd6mqj3Kv7ggsPBgmka4AMZ7T8bYpZVmpmZ6CiY3TfreTryAdTsRlrUu7vvA0psCUfFoZW4eaKByiv9E9C4ZNH8bRfxmX6/oH6zx7qBAoGBAPgXAPdyMtLFnFf6wlJmT6bFNdmxcSGDYKZBFBIujwCj1Lj9BAaTr6faydNdlAPAlQ0BD8uETam65S91szuo9Y/f1qfebaagapqDmsatpxL4gL+mk+pq6BEoufakirCt9eZSbu6WEbBwvFYcUcE68hX+bn9588No0tC7flBqJIk7AoGBANmOOHhAliiEZB/pGfzquUUCYs47nNVUwA3VFiGADkCMGHUe1OBWi8aFqg5ncF/w05X4ok7cuwi+ghX28HX30uSwE9/iu2y/BYURZEHh+zuqgtmOmYTg27b/HM/F/sdqwRD3jorvQw7jGswOp6Hs10Qr5XTleJxluPqW+M+j9qphAoGAEGrtTHLJPDuJkiTtESjmIoIwgxyEARTBo57w5hcgrYkp/af4yTqiyDfpXUx3DByFgJvOYMh7nzzYB+EBIHSY85F7khZdKUa6Z0lIR9ecE9xQIN60MTIr816/l7vh1bldaYWRA8b4mrWLMSOkoWX2OVBiHZuDby4TrW6K+GVdz5sCgYEAqOg0i5iJfJedLPFLzgNnKF7722aJE1AjdBnWftfvOMoMSxJO8IYBK3anW1uP8c0GWU+apGPXiqhuUyNP+icYw5i5NX/RSFLetDuWfVg4OFDgGo0OQA5cR6217tKoRqdpkgTB1LiCddJ5Aaj3GU7KqxDxq1pl0/deMKB6OKvyXMECgYAHv+910Fvqxja4CBo2MJ5WutqEmmQ7wL0TMaBLpK4IG6ZhERkX70FfWnR6bvHzcS+AVojFFPEbJ5B/UiBoGNyPqRoC8xwUQ56jZfZK1PGw+CpEkRbU849LeHg0sh60ZHKM6MxxVp2fNY4EDFrk82L5AicIWCqx39LoQ5n1oshpTw==",
		
		//异步通知地址
		'notify_url' => "http://pay.yitiger.com/api/Notify/notify_url.html",
		
		//同步跳转
		'return_url' => "http://pay.yitiger.com/api/Notify/return_url.html",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhvfAorQNsF0mKP04P6Jk27FdMgCei6XdoNHOmOMPjYhqj5zKW0n1akOnuJZMndP0ZUZfFmC3SVqsQG3K6J4tlU3XaJPZmVEQX+RIVAjH+6URSyIfRoaMnjb7ke+MJ5Cb7jVEg9nNr1v1G4GHuf6Eu51LQOexFB4eWSuS9pe+q+5yF2aSEiQ3vZNQDFp4axEzDAMWPfnN786aYO6FvGJ4ROfbXbKgqsWV7g/RUWreQZzTv03RbKtOvgMQ/2yUFHL/WCgFIDuK+4rTrUjHqjn30XH7n3uV7V47vK4ymZ70Cxo5igg8m0beH+1zbxnB82dyY1MUs2c6BwGq1bVkt0c6/wIDAQAB",
);