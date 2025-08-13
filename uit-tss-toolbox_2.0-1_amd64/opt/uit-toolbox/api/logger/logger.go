package logger

import (
	"fmt"
	"time"
)

func TimePrefix () (string) {
    return time.Now().Format("2006-01-02 15:04:05")
}

type Logger interface{
	Debug(message string)
	Info(message string)
	Warning(message string)
	Error(message string)
}


type ConsoleLogger struct {}
func (l *ConsoleLogger) Debug(message string) { fmt.Println(TimePrefix() + " [DEBUG] " + message) }
func (l *ConsoleLogger) Info(message string) { fmt.Println(TimePrefix() + " [INFO]  " + message) }
func (l *ConsoleLogger) Warning(message string) { fmt.Println(TimePrefix() + " [WARN]  " + message) }
func (l *ConsoleLogger) Error(message string) { fmt.Println(TimePrefix() + " [ERROR] " + message) }


func LoggerFactory(loggerType string) Logger {
    switch loggerType {
    case "console":
        return &ConsoleLogger{}
    default:
        return &ConsoleLogger{}
    }
}