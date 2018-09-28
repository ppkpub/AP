这是我们PPk技术社区尝试实现了一个融合比原链和PPk开放协议的小应用DEMO——“我画你猜”比原区块链版。
可以通过下面两种方式访问：
1.  传统的网址： http://btmdemo.ppkpub.org/joy/
2.  基于区块链的PPk ODIN标识网址： ppk:JOY/guessgame/ 

类似DAT、IPFS等正在发展中的WEB3.0开放协议，目前大众使用的电脑和手机浏览器还不能原生支持访问。要访问“ppk:joy/”这样的ODIN标识网址，现在可以运行我们PPk开发的JAVA开源工具的代理服务，就能使用现有浏览器来访问PPK网络资源了，比如 http://btmdemo.ppkpub.org:8088/ 或 http://45.32.19.146:8088/ 就是我们运行的示例服务，在浏览器里打开该代理服务网址然后输入要访问的 PPk ODIN标识网址就可以看到了。

源码是PHP+JS编写的，可以自行部署运行， 注意需编辑ppk_joyblock.inc.php文本文件，根据自己的比原钱包节点相应修改里面的节点API地址和账户等参数。
