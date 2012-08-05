-module(prflr).
-export([start/0, makemessage/1]).
-import(string, [tokens/2, to_integer/1, to_float/1]).

start() ->
    spawn(fun() -> server("127.0.0.1") end).

server(MongoHost) ->
    {ok, Socket} = gen_udp:open(4000, [binary, {active, false}]),
    io:format("Server opened socket:~p~n",[Socket]),
    application:start(mongodb),
    application:start(bson),
    Host = {MongoHost, 27017},
    {ok, Conn} = mongo:connect(Host),
    io:format("Conn to mongo is : ~p~n", [Conn]),
    loop(Socket, Conn).

loop(Socket, Conn) ->
    inet:setopts(Socket, [{active, once}]),
        receive
            {udp, Socket, Host, Port, Bin} ->
                io:format("Server received:~p~n",[Bin]),
                mongo:do (safe, master, Conn, prflr, fun() ->  
                   mongo:insert(timers, makemessage(Bin))
                end),      
                loop(Socket, Conn)
end.


makemessage(Bin) ->
    [Thread, Group, Timer, Duration, Info] = tokens(binary_to_list(Bin), "|"),
    {Fldr,[]} = to_float(Duration),
    {
                        thread, iolist_to_binary(Thread), 
                        timer,  iolist_to_binary(Timer), 
                        group,  iolist_to_binary(Group), 
                        duration, Fldr,
                        info,   iolist_to_binary(Info)
    }.


stop(_State) ->
    ok.
