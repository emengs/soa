1、Loger  文件夹内的文件是基础代码
2、test.php有使用案列
3、Loger包类未使用命名空间，是通过require_once 来包含文件，方便引入
如果是在非命名空间内引用，调用方式Log4p::error()；
若在声明的命名空间内引用，调用方式\Log4p::error()；
习惯使用命名空间的，适当修改下，执行时能自动加载就好。
4、ErrorHandle.php文件用来声明程序出现执行错误时，执行回调，记录日志。
包含程序执行时的error、notice、warming等。看项目需要保留具体需要记录的级别。
将文件放入项目适合的lib里面，合适的程序入口加载入此文件即可。
5、日志路径配置要求
A:统一要求日志记录存放在项目更目录下的xx项目\Runtime\Logs。
B:定义好APP_ROOT，\Logger\Logger\Log4p.php配置日志存放目录。
C:\Logger\Logger\File.php日志具体实现类。
日志存放格式为json，日志结构为time、error_type、type、request、respone。
可根据项目具体需要修改。
